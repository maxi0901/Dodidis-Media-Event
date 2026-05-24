<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err(405, 'POST erwartet.');
}
require_csrf();

$cfg = require __DIR__ . '/../config.php';

function ensure_project_upload_access(array $session, array $proj, string $kind): void {
    if (has_role('admin', 'manager')) {
        return;
    }

    $uid = (int)$session['uid'];
    if ($kind === 'rohmaterial' && (int)($proj['videograf_id'] ?? 0) !== $uid) {
        json_err(403, 'Keine Berechtigung für dieses Projekt.');
    }
    if ($kind === 'fertigstellung' && (int)($proj['cutter_id'] ?? 0) !== $uid) {
        json_err(403, 'Keine Berechtigung für dieses Projekt.');
    }
}


if (empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    json_err(400, 'Datei fehlt.');
}
$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_err(400, 'Upload-Fehler (Code ' . (int)$file['error'] . ').');
}
if ($file['size'] > $cfg['max_upload_bytes']) {
    json_err(413, 'Datei zu groß.');
}

$scope = $_GET['scope'] ?? 'project';
$kind  = $_GET['kind']  ?? 'other';
$refId = $_GET['id']    ?? '';

$customerKinds = ['vertrag','leistungsbeschreibung','avv','other'];
$projectKinds  = ['script','contract','correction','other','rohmaterial','fertigstellung'];
if ($scope === 'customer' && !in_array($kind, $customerKinds, true)) {
    json_err(400, 'Ungültiger kind-Parameter für customer-Scope.');
}
if ($scope === 'project' && !in_array($kind, $projectKinds, true)) {
    json_err(400, 'Ungültiger kind-Parameter für project-Scope.');
}
if ($scope === 'avatar' && $kind !== 'avatar') {
    $kind = 'avatar';
}

// ── Role-based access checks per scope ──────────────────────────────────────
if ($scope === 'avatar') {
    if (!has_role('admin', 'manager')) {
        json_err(403, 'Keine Berechtigung.');
    }
} elseif ($scope === 'customer') {
    if (!has_role('admin', 'manager')) {
        json_err(403, 'Keine Berechtigung.');
    }
} elseif ($scope === 'project') {
    if ($kind === 'rohmaterial') {
        if (!has_role('admin', 'manager', 'videograf')) {
            json_err(403, 'Keine Berechtigung.');
        }
    } elseif ($kind === 'fertigstellung') {
        if (!has_role('admin', 'manager', 'cutter')) {
            json_err(403, 'Keine Berechtigung.');
        }
    } else {
        if (!has_role('admin', 'manager')) {
            json_err(403, 'Keine Berechtigung.');
        }
    }
} elseif ($scope === 'contract') {
    if (!has_role('admin', 'manager', 'contract_uploader')) {
        json_err(403, 'Keine Berechtigung.');
    }
} elseif ($scope === 'voice') {
    if ($session['type'] === 'customer') {
        json_err(403, 'Keine Berechtigung.');
    }
} else {
    json_err(400, 'Unbekannter scope.');
}

// MIME über finfo verifizieren (Browser-Header sind nicht vertrauenswürdig)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

// Scope-specific MIME checks
if ($scope === 'contract') {
    $allowedContractMimes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    if (!in_array($mime, $allowedContractMimes, true)) {
        json_err(415, 'Nur PDF oder .docx Dateien sind für Verträge erlaubt.');
    }
} elseif ($scope === 'voice') {
    $allowedVoiceMimes = ['audio/webm', 'audio/ogg', 'audio/wav', 'audio/mpeg'];
    if (!in_array($mime, $allowedVoiceMimes, true)) {
        json_err(415, 'Dateityp nicht erlaubt: ' . $mime);
    }
} else {
    if (!in_array($mime, $cfg['allowed_mimes'], true)) {
        json_err(415, 'Dateityp nicht erlaubt: ' . $mime);
    }
}

// Zielpfad – Avatar: öffentlich (uploads/), Dokumente: privat (private_path/)
if ($scope === 'project') {
    if (!$refId) json_err(400, 'id (project) fehlt.');
    $proj = db_one("SELECT id, title, videograf_id, cutter_id FROM projects WHERE id = ?", [$refId]);
    if (!$proj) json_err(404, 'Projekt nicht gefunden.');

    ensure_project_upload_access($session, $proj, $kind);

    $basePath = $cfg['private_path'];
    $relDir   = 'projects/' . $refId;
    $dir      = $basePath . '/' . $relDir;
} elseif ($scope === 'avatar') {
    if (!$refId) json_err(400, 'id (user) fehlt.');
    $dir    = $cfg['uploads_path'] . '/avatars/' . $refId;
    $urlDir = $cfg['uploads_url']  . '/avatars/' . $refId;
} elseif ($scope === 'customer') {
    if (!$refId) json_err(400, 'id (customer) fehlt.');
    $cust = db_one("SELECT id FROM customers WHERE id = ?", [$refId]);
    if (!$cust) json_err(404, 'Kunde nicht gefunden.');
    $basePath = $cfg['private_path'];
    $relDir   = 'customers/' . $refId;
    $dir      = $basePath . '/' . $relDir;
} elseif ($scope === 'contract') {
    if (!$refId) json_err(400, 'id (contract) fehlt.');
    $contract = db_one("SELECT id, customer_id FROM contracts WHERE id = ?", [$refId]);
    if (!$contract) json_err(404, 'Vertrag nicht gefunden.');
    $basePath = $cfg['private_path'];
    $relDir   = 'contracts/' . $refId;
    $dir      = $basePath . '/' . $relDir;
} elseif ($scope === 'voice') {
    if (!$refId) json_err(400, 'id (contract_comment) fehlt.');
    $comment = db_one("SELECT id FROM contract_comments WHERE id = ?", [$refId]);
    if (!$comment) json_err(404, 'Kommentar nicht gefunden.');
    $basePath = $cfg['private_path'];
    $relDir   = 'contract_comments/' . $refId;
    $dir      = $basePath . '/' . $relDir;
}

if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
    json_err(500, 'Upload-Ordner konnte nicht angelegt werden: ' . $dir);
}

$safeName = preg_replace('/[^a-zA-Z0-9._\-]+/', '_', $file['name']);
$safeName = substr($safeName, 0, 100);
$filename = uid('f') . '_' . $safeName;
$fullPath = $dir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
    json_err(500, 'Datei konnte nicht gespeichert werden.');
}
@chmod($fullPath, 0644);

$response = [
    'filename' => $safeName,
    'mime'     => $mime,
    'size'     => (int)$file['size'],
];

if ($scope === 'project') {
    $relPath = $relDir . '/' . $filename;
    db_exec(
        "INSERT INTO project_files (project_id, kind, filename, mime, size, path, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$refId, $kind, $safeName, $mime, (int)$file['size'], $relPath, $session['uid']]
    );
    $response['id']   = (int)db()->lastInsertId();
    $response['path'] = $relPath;
    log_activity('project', $refId, 'fileUploaded', ['kind' => $kind, 'filename' => $safeName]);

    if ($kind === 'rohmaterial') {
        $cutterId = $proj['cutter_id'] ?? null;
        if ($cutterId) {
            db_exec(
                "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type)
                 VALUES (?, 'rohmaterial_uploaded', 'Rohmaterial hochgeladen', ?, ?, 'project')",
                [$cutterId, $proj['title'], $refId]
            );
        }
    } elseif ($kind === 'fertigstellung') {
        $adminsManagers = db_all(
            "SELECT DISTINCT user_id FROM user_roles WHERE role_name IN ('admin','manager')"
        );
        foreach ($adminsManagers as $am) {
            db_exec(
                "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type)
                 VALUES (?, 'fertigstellung_uploaded', 'Fertigstellung hochgeladen', ?, ?, 'project')",
                [$am['user_id'], $proj['title'], $refId]
            );
        }
    }
} elseif ($scope === 'avatar') {
    $response['path'] = $urlDir . '/' . $filename;
} elseif ($scope === 'customer') {
    $relPath = $relDir . '/' . $filename;
    db_exec(
        "INSERT INTO customer_files (customer_id, kind, filename, mime, size, path, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$refId, $kind, $safeName, $mime, (int)$file['size'], $relPath, $session['uid']]
    );
    $response['id']   = (int)db()->lastInsertId();
    $response['path'] = $relPath;
    log_activity('customer', $refId, 'fileUploaded', ['kind' => $kind, 'filename' => $safeName]);
} elseif ($scope === 'contract') {
    $relPath = $relDir . '/' . $filename;
    db_exec(
        "UPDATE contracts SET filename=?, mime=?, size=?, path=? WHERE id=?",
        [$safeName, $mime, (int)$file['size'], $relPath, $refId]
    );
    $response['id']   = $refId;
    $response['path'] = $relPath;
    log_activity('contract', $refId, 'fileUploaded', ['filename' => $safeName]);
} elseif ($scope === 'voice') {
    $relPath = $relDir . '/' . $filename;
    db_exec(
        "UPDATE contract_comments SET voice_path=?, voice_filename=? WHERE id=?",
        [$relPath, $safeName, $refId]
    );
    $response['id']   = $refId;
    $response['path'] = $relPath;
    log_activity('contract', $refId, 'voiceUploaded', ['filename' => $safeName]);
}

json_ok($response);
