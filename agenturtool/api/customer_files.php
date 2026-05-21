<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function map_customer_file(array $f): array {
    return [
        'id'         => (int)$f['id'],
        'customerId' => $f['customer_id'],
        'kind'       => $f['kind'],
        'filename'   => $f['filename'],
        'mime'       => $f['mime'],
        'size'       => (int)$f['size'],
        'path'       => $f['path'],
        'uploadedBy' => $f['uploaded_by'],
        'uploadedAt' => $f['uploaded_at'],
    ];
}

if ($method === 'GET') {
    $customerId = $_GET['customer_id'] ?? '';
    if (!$customerId) json_err(400, 'customer_id fehlt.');

    if ($session['type'] === 'customer' && $session['cid'] !== $customerId) {
        json_err(403, 'Keine Berechtigung.');
    }

    $rows = db_all(
        "SELECT id, customer_id, kind, filename, mime, size, path, uploaded_by, uploaded_at
         FROM customer_files WHERE customer_id = ? ORDER BY uploaded_at DESC",
        [$customerId]
    );
    json_ok(array_map('map_customer_file', $rows));
}

if ($method === 'DELETE') {
    require_csrf();
    require_role('admin', 'manager');

    $fileId = (int)($_GET['id'] ?? 0);
    if (!$fileId) json_err(400, 'id fehlt.');

    $cfg  = require __DIR__ . '/../config.php';
    $file = db_one(
        "SELECT id, path, customer_id, filename FROM customer_files WHERE id = ?",
        [$fileId]
    );
    if (!$file) json_err(404, 'Datei nicht gefunden.');

    $fsPath = str_replace($cfg['uploads_url'], $cfg['uploads_path'], $file['path']);
    if (file_exists($fsPath)) @unlink($fsPath);

    db_exec("DELETE FROM customer_files WHERE id = ?", [$fileId]);
    log_activity('customer', $file['customer_id'], 'fileDeleted', ['filename' => $file['filename']]);

    json_ok(['deleted' => true]);
}

json_err(405, 'Methode nicht erlaubt.');
