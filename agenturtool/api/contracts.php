<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

/**
 * Speichert die hochgeladene, fertig unterschriebene PDF unter contracts/{id}/ ab.
 * Erwartet $_FILES['file'] (application/pdf). Gibt [relPath, filename, mime, size, sha256] zurück.
 */
function contract_store_signed_pdf(string $contractId, array $session): array
{
    $cfg = require __DIR__ . '/../config.php';
    if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        json_err(400, 'Datei fehlt.');
    }
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) json_err(400, 'Upload-Fehler (Code ' . (int)$file['error'] . ').');
    if ($file['size'] > $cfg['max_upload_bytes']) json_err(413, 'Datei zu groß.');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if ($mime !== 'application/pdf') json_err(415, 'Nur PDF erlaubt.');

    $dir = $cfg['private_path'] . '/contracts/' . $contractId;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        json_err(500, 'Upload-Ordner konnte nicht angelegt werden.');
    }
    $safeName = preg_replace('/[^a-zA-Z0-9._\-]+/', '_', $file['name']);
    $safeName = substr($safeName, 0, 100) ?: 'vertrag.pdf';
    $filename = 'signed_' . uid('f') . '_' . $safeName;
    $fullPath = $dir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        json_err(500, 'Datei konnte nicht gespeichert werden.');
    }
    @chmod($fullPath, 0644);
    $hash = hash_file('sha256', $fullPath);
    return ['contracts/' . $contractId . '/' . $filename, $safeName, $mime, (int)$file['size'], $hash];
}

$session  = require_login();
$isStaff  = $session['type'] !== 'customer';
$method   = $_SERVER['REQUEST_METHOD'];
$id       = $_GET['id'] ?? null;
$action   = $_GET['action'] ?? '';

// Kunden dürfen ausschließlich: eigene Verträge lesen (GET) und eigene Verträge
// unterschreiben (POST action=customer-sign). Alles andere ist Staff-Gebiet.
if (!$isStaff) {
    $isCustomerSign = ($method === 'POST' && $action === 'customer-sign');
    if ($method !== 'GET' && !$isCustomerSign) {
        json_err(403, 'Keine Berechtigung.');
    }
} else {
    if (!has_role('admin', 'manager', 'contract_uploader')) {
        json_err(403, 'Keine Berechtigung.');
    }
}

if (in_array($method, ['POST','PUT','DELETE'], true)) require_csrf();

$baseSelect = "SELECT id, customer_id AS customerId, title, status, filename, mime, size, path,
                      agency_signer_name AS agencySignerName, agency_signed_at AS agencySignedAt,
                      customer_signer_name AS customerSignerName, customer_signed_at AS customerSignedAt,
                      signed_filename AS signedFilename, signed_size AS signedSize, signed_at AS signedAt,
                      uploaded_by AS uploadedBy, created_at AS createdAt, updated_at AS updatedAt
                 FROM contracts";

switch ($method) {
    case 'GET':
        if ($id) {
            $c = db_one("$baseSelect WHERE id = ?", [$id]);
            if (!$c) json_err(404, 'Vertrag nicht gefunden.');
            // Agentur-Signaturbild nur bei Einzelabruf mitliefern (für die finale PDF beim Kunden).
            $sig = db_one("SELECT agency_signature AS agencySignature FROM contracts WHERE id = ?", [$id]);
            $c['agencySignature'] = $sig['agencySignature'] ?? null;
            // Kunden dürfen nur eigene, signierbare/signierte Verträge sehen (keine Drafts).
            if (!$isStaff) {
                if ($c['customerId'] !== $session['cid']
                    || !in_array($c['status'], ['awaiting_signature','signed'], true)) {
                    json_err(403, 'Keine Berechtigung.');
                }
                json_ok($c);
            }
            $c['comments'] = db_all(
                "SELECT id, user_id AS userId, comment_text AS text, voice_path AS voicePath,
                        voice_filename AS voiceFilename, created_at AS createdAt
                   FROM contract_comments WHERE contract_id = ? ORDER BY created_at ASC",
                [$id]
            );
            json_ok($c);
        }
        if (!$isStaff) {
            $rows = db_all(
                "$baseSelect WHERE customer_id = ? AND status IN ('awaiting_signature','signed')
                   ORDER BY created_at DESC",
                [$session['cid']]
            );
            json_ok($rows);
        }
        $rows = db_all("$baseSelect ORDER BY created_at DESC");
        json_ok($rows);

    case 'POST':
        // ── 1. Agentur-Vorsignatur (Staff): zeichnet Unterschrift, sendet zur Kundenunterschrift ──
        if ($action === 'presign') {
            $b = input_json();
            $c = db_one("SELECT id, status, mime, path, customer_id FROM contracts WHERE id = ?", [$id]);
            if (!$c) json_err(404, 'Vertrag nicht gefunden.');
            if (!$c['path'] || ($c['mime'] ?? '') !== 'application/pdf') {
                json_err(400, 'Nur PDF-Verträge können digital unterschrieben werden. Bitte als PDF hochladen.');
            }
            if (!$c['customer_id']) json_err(400, 'Dem Vertrag ist kein Kunde zugeordnet.');
            if (!in_array($c['status'], ['draft','confirmed','awaiting_signature'], true)) {
                json_err(409, 'Vertrag kann in diesem Status nicht vorsigniert werden.');
            }
            $sig  = (string)($b['agencySignature'] ?? '');
            $name = s($b['agencySignerName'] ?? null, 255);
            if (strpos($sig, 'data:image/') !== 0) json_err(400, 'Unterschrift fehlt.');
            if (!$name) json_err(400, 'Name der unterschreibenden Person fehlt.');

            $cfg    = require __DIR__ . '/../config.php';
            $fsPath = $cfg['private_path'] . '/' . $c['path'];
            $origHash = is_readable($fsPath) ? hash_file('sha256', $fsPath) : null;

            db_exec(
                "UPDATE contracts
                    SET agency_signed_by = ?, agency_signer_name = ?, agency_signed_at = NOW(),
                        agency_signature = ?, original_hash = ?, status = 'awaiting_signature'
                  WHERE id = ?",
                [$session['uid'], $name, $sig, $origHash, $id]
            );
            log_activity('contract', $id, 'presigned', ['signer' => $name]);
            $row = db_one("$baseSelect WHERE id = ?", [$id]);
            json_ok($row);
        }

        // ── 2. Kundenunterschrift: fertige signierte PDF wird hochgeladen (multipart) ──
        if ($action === 'customer-sign') {
            $c = db_one("SELECT id, customer_id, status FROM contracts WHERE id = ?", [$id]);
            if (!$c) json_err(404, 'Vertrag nicht gefunden.');
            if ($c['customer_id'] !== $session['cid']) json_err(403, 'Keine Berechtigung.');
            if ($c['status'] !== 'awaiting_signature') json_err(409, 'Vertrag steht nicht zur Unterschrift bereit.');

            $name    = s($_POST['customerSignerName'] ?? null, 255);
            $consent = ($_POST['consent'] ?? '') === '1' || ($_POST['consent'] ?? '') === 'true';
            $custSig = (string)($_POST['customerSignature'] ?? '');
            if (!$name)   json_err(400, 'Name fehlt.');
            if (!$consent) json_err(400, 'Einwilligung erforderlich.');

            [$relPath, $fname, $smime, $ssize, $shash] = contract_store_signed_pdf($id, $session);

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            db_exec(
                "UPDATE contracts
                    SET signed_filename = ?, signed_mime = ?, signed_size = ?, signed_path = ?,
                        signed_hash = ?, signed_at = NOW(),
                        customer_signed_at = NOW(), customer_signer_name = ?, customer_signer_ip = ?,
                        customer_consent_at = NOW(), customer_signature = ?, status = 'signed'
                  WHERE id = ?",
                [$fname, $smime, $ssize, $relPath, $shash, $name, $ip,
                 (strpos($custSig, 'data:image/') === 0 ? $custSig : null), $id]
            );
            // Onboarding-Checkliste markieren (falls vorhanden)
            db_exec("UPDATE customer_checklists SET contract_signed = 1 WHERE customer_id = ?", [$c['customer_id']]);
            // Staff informieren
            $staff = db_all("SELECT DISTINCT user_id FROM user_roles WHERE role_name IN ('admin','manager')");
            foreach ($staff as $st) {
                db_exec(
                    "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type)
                     VALUES (?, 'contract_signed', 'Vertrag unterschrieben', ?, ?, 'contract')",
                    [$st['user_id'], $name . ' hat einen Vertrag unterschrieben.', $id]
                );
            }
            log_activity('contract', $id, 'customerSigned', ['signer' => $name, 'ip' => $ip]);
            $row = db_one("$baseSelect WHERE id = ?", [$id]);
            json_ok($row);
        }

        // ── 3. Staff lädt bereits (extern) unterschriebenen Vertrag direkt hoch (multipart) ──
        if ($action === 'upload-signed') {
            $c = db_one("SELECT id, customer_id, status FROM contracts WHERE id = ?", [$id]);
            if (!$c) json_err(404, 'Vertrag nicht gefunden.');
            if (!$c['customer_id']) json_err(400, 'Dem Vertrag ist kein Kunde zugeordnet.');

            [$relPath, $fname, $smime, $ssize, $shash] = contract_store_signed_pdf($id, $session);
            db_exec(
                "UPDATE contracts
                    SET signed_filename = ?, signed_mime = ?, signed_size = ?, signed_path = ?,
                        signed_hash = ?, signed_at = NOW(), status = 'signed'
                  WHERE id = ?",
                [$fname, $smime, $ssize, $relPath, $shash, $id]
            );
            log_activity('contract', $id, 'signedUploaded', ['filename' => $fname]);
            $row = db_one("$baseSelect WHERE id = ?", [$id]);
            json_ok($row);
        }

        // ── Standard: neuen Vertrag (draft) anlegen ──
        $b = input_json();
        $title = s($b['title'] ?? null, 255);
        if (!$title) json_err(400, 'title ist Pflicht.');
        $newId = uid('ctr');
        db_exec(
            "INSERT INTO contracts (id, customer_id, title, status, uploaded_by) VALUES (?, ?, ?, 'draft', ?)",
            [$newId, $b['customerId'] ?? null, $title, $session['uid']]
        );
        // Notify admins and managers when contract_uploader creates a contract
        if (!has_role('admin', 'manager')) {
            $admins = db_all("SELECT DISTINCT user_id FROM user_roles WHERE role_name IN ('admin','manager')");
            foreach ($admins as $a) {
                if ($a['user_id'] === $session['uid']) continue;
                db_exec(
                    "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type) VALUES (?, 'contract_uploaded', ?, ?, ?, 'contract')",
                    [$a['user_id'], 'Neuer Vertrag', $title, $newId]
                );
            }
        }
        log_activity('contract', $newId, 'created');
        $row = db_one("$baseSelect WHERE id = ?", [$newId]);
        $row['comments'] = [];
        json_ok($row, 201);

    case 'PUT':
        if (!$id) json_err(400, 'id fehlt.');
        $c = db_one("SELECT id, status FROM contracts WHERE id = ?", [$id]);
        if (!$c) json_err(404, 'Vertrag nicht gefunden.');
        $b = input_json();
        $set = []; $vals = [];
        if (array_key_exists('title', $b))      { $set[] = 'title = ?';       $vals[] = s($b['title'], 255); }
        if (array_key_exists('customerId', $b)) { $set[] = 'customer_id = ?'; $vals[] = $b['customerId'] ?: null; }
        if (array_key_exists('status', $b)) {
            if (!in_array($b['status'], ['draft','confirmed','awaiting_signature','signed'], true)) json_err(400, 'Ungültiger Status.');
            if (!has_role('admin','manager')) json_err(403, 'Nur Admin/Manager können Status ändern.');
            $set[] = 'status = ?'; $vals[] = $b['status'];
        }
        if ($set) {
            $vals[] = $id;
            db_exec("UPDATE contracts SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        }
        log_activity('contract', $id, 'updated');
        $row = db_one("$baseSelect WHERE id = ?", [$id]);
        $row['comments'] = db_all(
            "SELECT id, user_id AS userId, comment_text AS text, voice_path AS voicePath,
                    voice_filename AS voiceFilename, created_at AS createdAt
               FROM contract_comments WHERE contract_id = ? ORDER BY created_at ASC",
            [$id]
        );
        json_ok($row);

    case 'DELETE':
        require_role('admin');
        if (!$id) json_err(400, 'id fehlt.');
        db_exec("DELETE FROM contract_comments WHERE contract_id = ?", [$id]);
        db_exec("DELETE FROM contracts WHERE id = ?", [$id]);
        log_activity('contract', $id, 'deleted');
        json_ok(['id' => $id]);

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
