<?php
declare(strict_types=1);

/**
 * POST /api/content_published.php
 * n8n meldet zurück, dass ein Inhalt veröffentlicht wurde.
 * Body: { "content_id": 123, "platform_response": "..." }
 * Auth: Header X-API-KEY.
 */

require_once __DIR__ . '/../includes/api_auth.php';

require_api_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    api_send(405, ['success' => false, 'error' => 'Method not allowed']);
}

$body      = input_json();
$contentId = api_require_content_id($body);

$row = db_one("SELECT id, status FROM content_queue WHERE id = ?", [$contentId]);
if (!$row) {
    api_send(404, ['success' => false, 'error' => 'Content not found']);
}
// Nur freigegebene Einträge dürfen veröffentlicht werden (Flow: draft → approved → published).
// Schützt vor stale/vertippten content_id, die sonst Entwürfe/abgelehnte Items „published" setzen.
if ($row['status'] !== 'approved') {
    api_send(409, ['success' => false, 'error' => 'Content is not in approved state']);
}

$set    = ["status = 'published'", "published_at = NOW()"];
$params = [];

if (api_has_column('content_queue', 'platform_response')) {
    $set[]    = 'platform_response = ?';
    $params[] = isset($body['platform_response']) ? (string)$body['platform_response'] : null;
}
if (api_has_column('content_queue', 'updated_at')) {
    $set[] = 'updated_at = NOW()';
}

$params[] = $contentId;
// status='approved' zusätzlich in der WHERE-Klausel → atomar gegen parallele Polls.
$affected = db_exec("UPDATE content_queue SET " . implode(', ', $set) . " WHERE id = ? AND status = 'approved'", $params);
if ($affected < 1) {
    api_send(409, ['success' => false, 'error' => 'Content is not in approved state']);
}

api_send(200, ['success' => true, 'message' => 'Content marked as published']);
