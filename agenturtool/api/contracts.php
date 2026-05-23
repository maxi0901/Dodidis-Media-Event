<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');

// Only admin, manager, contract_uploader can access contracts
if (!has_role('admin', 'manager', 'contract_uploader')) {
    json_err(403, 'Keine Berechtigung.');
}

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
if (in_array($method, ['POST','PUT','DELETE'], true)) require_csrf();

$baseSelect = "SELECT id, customer_id AS customerId, title, status, filename, mime, size, path,
                      uploaded_by AS uploadedBy, created_at AS createdAt, updated_at AS updatedAt
                 FROM contracts";

switch ($method) {
    case 'GET':
        if ($id) {
            $c = db_one("$baseSelect WHERE id = ?", [$id]);
            if (!$c) json_err(404, 'Vertrag nicht gefunden.');
            // attach comments
            $c['comments'] = db_all(
                "SELECT id, user_id AS userId, comment_text AS text, voice_path AS voicePath,
                        voice_filename AS voiceFilename, created_at AS createdAt
                   FROM contract_comments WHERE contract_id = ? ORDER BY created_at ASC",
                [$id]
            );
            json_ok($c);
        }
        $rows = db_all("$baseSelect ORDER BY created_at DESC");
        json_ok($rows);

    case 'POST':
        if (!has_role('contract_uploader')) json_err(403, 'Nur der autorisierte Vertrags-Hochlader darf Verträge anlegen.');
        $b = input_json();
        $title = s($b['title'] ?? null, 255);
        if (!$title) json_err(400, 'title ist Pflicht.');
        $newId = uid('ctr');
        db_exec(
            "INSERT INTO contracts (id, customer_id, title, status, uploaded_by) VALUES (?, ?, ?, 'draft', ?)",
            [$newId, $b['customerId'] ?? null, $title, $session['uid']]
        );
        // Notify admins and managers
        $admins = db_all("SELECT DISTINCT user_id FROM user_roles WHERE role_name IN ('admin','manager')");
        foreach ($admins as $a) {
            if ($a['user_id'] === $session['uid']) continue;
            db_exec(
                "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type) VALUES (?, 'contract_uploaded', ?, ?, ?, 'contract')",
                [$a['user_id'], 'Neuer Vertrag', $title, $newId]
            );
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
            if (!in_array($b['status'], ['draft','confirmed'], true)) json_err(400, 'Ungültiger Status.');
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
