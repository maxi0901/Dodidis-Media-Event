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
    $variant = $_GET['variant'] ?? '';
    $c = db_one(
        "SELECT id, customer_id, status, filename, mime, path,
                signed_filename, signed_mime, signed_path
           FROM contracts WHERE id = ?",
        [$fileId]
    );
    if (!$c) json_err(404, 'Datei nicht gefunden.');

    if ($session['type'] === 'customer') {
        // Kunde darf nur eigene Verträge laden – und nur, wenn sie zur Unterschrift
        // anstehen (Original zum Signieren) oder bereits unterschrieben sind (fertiges Dokument).
        if ($session['cid'] !== $c['customer_id']) json_err(403, 'Keine Berechtigung.');
        if (!in_array($c['status'], ['awaiting_signature', 'signed'], true)) {
            json_err(403, 'Keine Berechtigung.');
        }
        if ($c['status'] === 'signed' && $c['signed_path']) {
            $file = ['filename' => $c['signed_filename'] ?: $c['filename'],
                     'mime'     => $c['signed_mime'] ?: $c['mime'],
                     'path'     => $c['signed_path']];
        } else {
            $file = ['filename' => $c['filename'], 'mime' => $c['mime'], 'path' => $c['path']];
        }
    } else {
        if (!has_role('admin','manager','contract_uploader')) json_err(403, 'Keine Berechtigung.');
        if ($variant === 'signed' && $c['signed_path']) {
            $file = ['filename' => $c['signed_filename'] ?: $c['filename'],
                     'mime'     => $c['signed_mime'] ?: $c['mime'],
                     'path'     => $c['signed_path']];
        } else {
            $file = ['filename' => $c['filename'], 'mime' => $c['mime'], 'path' => $c['path']];
        }
    }
    if (!$file['path']) json_err(404, 'Datei nicht gefunden.');
} elseif ($scope === 'voice') {
    if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');
    if (!has_role('admin','manager','contract_uploader')) json_err(403, 'Keine Berechtigung.');
    $file = db_one(
        "SELECT id, voice_filename AS filename, COALESCE(mime, 'audio/webm') AS mime, voice_path AS path FROM contract_comments WHERE id = ?",
        [$fileId]
    );
    if (!$file || !$file['path']) json_err(404, 'Datei nicht gefunden.');
} elseif ($scope === 'voice_project') {
    if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');
    $file = db_one(
        "SELECT id, COALESCE(voice_filename, 'voice.webm') AS filename,
                COALESCE(mime, 'audio/webm') AS mime, voice_path AS path
           FROM project_comments WHERE id = ?",
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

// Audio-Sprachnachrichten inline ausliefern (für <audio>-Tag im Browser)
$isAudioScope = in_array($scope, ['voice', 'voice_project'], true);
$disposition  = $isAudioScope ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($fsPath));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($fsPath);
exit;
