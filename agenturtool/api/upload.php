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
require_role('admin', 'manager');

$cfg = require __DIR__ . '/../config.php';

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
$projectKinds  = ['script','contract','correction','other'];
if ($scope === 'customer' && !in_array($kind, $customerKinds, true)) {
    json_err(400, 'Ungültiger kind-Parameter für customer-Scope.');
}
if ($scope === 'project' && !in_array($kind, $projectKinds, true)) {
    json_err(400, 'Ungültiger kind-Parameter für project-Scope.');
}
if ($scope === 'avatar' && $kind !== 'avatar') {
    $kind = 'avatar';
}

// MIME über finfo verifizieren (Browser-Header sind nicht vertrauenswürdig)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!in_array($mime, $cfg['allowed_mimes'], true)) {
    json_err(415, 'Dateityp nicht erlaubt: ' . $mime);
}

// Zielpfad – Avatar: öffentlich (uploads/), Dokumente: privat (private_path/)
if ($scope === 'project') {
    if (!$refId) json_err(400, 'id (project) fehlt.');
    $proj = db_one("SELECT id FROM projects WHERE id = ?", [$refId]);
    if (!$proj) json_err(404, 'Projekt nicht gefunden.');
    $basePath = $cfg['private_path'];
    $relDir   = 'projects/' . $refId;
    $dir      = $basePath . '/' . $relDir;
} elseif ($scope === 'avatar') {
    if (!$refId) json_err(400, 'id (user) fehlt.');
    $dir      = $cfg['uploads_path'] . '/avatars/' . $refId;
    $urlDir   = $cfg['uploads_url']  . '/avatars/' . $refId;
} elseif ($scope === 'customer') {
    if (!$refId) json_err(400, 'id (customer) fehlt.');
    $cust = db_one("SELECT id FROM customers WHERE id = ?", [$refId]);
    if (!$cust) json_err(404, 'Kunde nicht gefunden.');
    $basePath = $cfg['private_path'];
    $relDir   = 'customers/' . $refId;
    $dir      = $basePath . '/' . $relDir;
} else {
    json_err(400, 'Unbekannter scope.');
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
}

json_ok($response);
