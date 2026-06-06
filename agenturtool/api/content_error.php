<?php
declare(strict_types=1);

/**
 * POST /api/content_error.php
 * n8n meldet einen Fehler beim Veröffentlichen zurück.
 * Body: { "content_id": 123, "error_message": "..." }
 * Auth: Header X-API-KEY.
 */

require_once __DIR__ . '/../includes/api_auth.php';

require_api_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    api_send(405, ['success' => false, 'error' => 'Method not allowed']);
}

$body      = input_json();
$contentId = api_require_content_id($body);

$row = db_one("SELECT id FROM content_queue WHERE id = ?", [$contentId]);
if (!$row) {
    api_send(404, ['success' => false, 'error' => 'Content not found']);
}

$set    = ["status = 'error'"];
$params = [];

if (api_has_column('content_queue', 'error_message')) {
    $set[]    = 'error_message = ?';
    $params[] = isset($body['error_message']) ? (string)$body['error_message'] : null;
}
if (api_has_column('content_queue', 'updated_at')) {
    $set[] = 'updated_at = NOW()';
}

$params[] = $contentId;
db_exec("UPDATE content_queue SET " . implode(', ', $set) . " WHERE id = ?", $params);

api_send(200, ['success' => true, 'message' => 'Content marked as error']);
