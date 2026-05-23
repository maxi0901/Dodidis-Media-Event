<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$fileId  = $_GET['id'] ?? '';
$scope   = trim($_GET['scope'] ?? '');

if ($fileId === '' || $fileId === '0') json_err(400, 'id fehlt.');

if ($scope === 'customer') {
    $file = db_one(
        "SELECT id, customer_id, filename, mime, size, path FROM customer_files WHERE id = ?",
        [(int)$fileId]
    );
    if (!$file) json_err(404, 'Datei nicht gefunden.');

    // Kunden sehen nur eigene Dateien; Mitarbeiter sehen alle
    if ($session['type'] === 'customer' && $session['cid'] !== $file['customer_id']) {
        json_err(403, 'Keine Berechtigung.');
    }
} elseif ($scope === 'project') {
    // Alle eingeloggten Mitarbeiter dürfen Projektdateien herunterladen
    if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');
    $file = db_one(
        "SELECT id, project_id, filename, mime, size, path FROM project_files WHERE id = ?",
        [(int)$fileId]
    );
    if (!$file) json_err(404, 'Datei nicht gefunden.');
} elseif ($scope === 'contract') {
    if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');
    if (!has_role('admin','manager','contract_uploader')) json_err(403, 'Keine Berechtigung.');
    $file = db_one(
        "SELECT id, filename, mime, size, path FROM contracts WHERE id = ?",
        [$fileId]
    );
    if (!$file || !$file['path']) json_err(404, 'Datei nicht gefunden.');
} elseif ($scope === 'voice') {
    if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');
    if (!has_role('admin','manager','contract_uploader')) json_err(403, 'Keine Berechtigung.');
    $file = db_one(
        "SELECT id, voice_filename AS filename, 'audio/webm' AS mime, voice_path AS path FROM contract_comments WHERE id = ?",
        [$fileId]
    );
    if (!$file || !$file['path']) json_err(404, 'Datei nicht gefunden.');
} else {
    json_err(400, 'Unbekannter scope.');
}

$cfg    = require __DIR__ . '/../config.php';
$fsPath = $cfg['private_path'] . '/' . $file['path'];

if (!file_exists($fsPath) || !is_readable($fsPath)) {
    json_err(404, 'Datei nicht mehr vorhanden.');
}

// Dateiname für Content-Disposition sauber machen
$downloadName = preg_replace('/[^\w.\-]+/', '_', $file['filename']);
$mime         = $file['mime'] ?: 'application/octet-stream';

// Alle Output-Buffer leeren, bevor Header gesetzt werden
while (ob_get_level()) ob_end_clean();

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($fsPath));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($fsPath);
exit;
