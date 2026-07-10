<?php
declare(strict_types=1);

/**
 * Cron-Runner: fällige, freigegebene Instagram-Posts automatisch veröffentlichen.
 * Ersetzt n8n als Scheduler — z. B. jede Minute vom vServer-Cron aufgerufen:
 *
 *   curl -fsS "https://dodidis-media.de/agenturtool/api/publish_due.php" -H "X-API-KEY: <key>"
 *
 * Auth: X-API-KEY == config.php['api_key'] (alternativ ?key=). Keine Session.
 * Veröffentlicht content_queue-Einträge mit status='approved', platform='instagram',
 * scheduled_at <= jetzt und published_at IS NULL. Max. 10 pro Lauf (Rate-Limits).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/meta_publish.php';

$cfg    = require __DIR__ . '/../config.php';
$apiKey = (string)($cfg['api_key'] ?? '');
$given  = (string)($_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? ''));
if ($apiKey === '' || !hash_equals($apiKey, $given)) {
    json_err(401, 'Ungültiger API-Key.');
}

@set_time_limit(0);

$due = db_all(
    "SELECT cq.id, cq.caption, cq.asset_id, a.content_type, p.customer_id
       FROM content_queue cq
       JOIN assets   a ON a.id = cq.asset_id
       JOIN projects p ON p.id = a.project_id
      WHERE cq.platform = 'instagram'
        AND cq.status = 'approved'
        AND cq.published_at IS NULL
        AND cq.scheduled_at IS NOT NULL
        AND cq.scheduled_at <= NOW()
      ORDER BY cq.scheduled_at ASC
      LIMIT 10"
);

$results = [];
foreach ($due as $row) {
    try {
        $res = ig_publish_asset(
            [
                'id'           => (string)$row['asset_id'],
                'customer_id'  => (string)$row['customer_id'],
                'content_type' => (string)$row['content_type'],
            ],
            (string)($row['caption'] ?? '')
        );
        db_exec(
            "UPDATE content_queue
                SET status='published', published_at=NOW(), platform_response=?, error_message=NULL
              WHERE id=?",
            [json_encode($res), $row['id']]
        );
        log_activity('content', (string)$row['asset_id'], 'published', ['via' => 'cron', 'ig_media' => $res['id']]);
        $results[] = ['id' => (int)$row['id'], 'ok' => true, 'mediaId' => $res['id']];
    } catch (\Throwable $e) {
        db_exec("UPDATE content_queue SET status='error', error_message=? WHERE id=?", [$e->getMessage(), $row['id']]);
        $results[] = ['id' => (int)$row['id'], 'ok' => false, 'error' => $e->getMessage()];
    }
}

json_ok(['processed' => count($results), 'results' => $results]);
