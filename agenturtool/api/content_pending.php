<?php
declare(strict_types=1);

/**
 * GET /api/content_pending.php
 * n8n holt alle freigegebenen, fälligen Instagram-Storys ab.
 * Auth: Header X-API-KEY.
 */

require_once __DIR__ . '/../includes/api_auth.php';

require_api_key();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    api_send(405, ['success' => false, 'error' => 'Method not allowed']);
}

$rows = db_all(
    "SELECT id, customer_id, platform, content_type, caption, media_url, scheduled_at
       FROM content_queue
      WHERE status = 'approved'
        AND platform = 'instagram'
        AND content_type = 'story'
        AND media_url IS NOT NULL
        AND media_url <> ''
        AND scheduled_at IS NOT NULL
        AND scheduled_at <= NOW()
        AND published_at IS NULL
      ORDER BY scheduled_at ASC"
);

$items = array_map(static function (array $r): array {
    $r['id'] = (int)$r['id'];
    return $r;
}, $rows);

api_send(200, ['success' => true, 'items' => $items]);
