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

// Überlappungsschutz: nur EIN Lauf gleichzeitig — verhindert Doppel-Postings,
// wenn ein Cron-Lauf länger als das Intervall dauert (Reels-Verarbeitung).
$lock = fopen(sys_get_temp_dir() . '/publish_due.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    json_ok(['skipped' => 'bereits ein Lauf aktiv', 'processed' => 0]);
}

@set_time_limit(0);

// scheduled_at wird als lokale Wandzeit (Europe/Berlin) gespeichert, der
// Server-PHP läuft aber auf UTC → Fälligkeit gegen die BERLINER Uhrzeit
// vergleichen, nicht gegen MySQL-NOW() (das wäre UTC → 1-2 h zu spät).
$nowBerlin = (new DateTime('now', new DateTimeZone('Europe/Berlin')))->format('Y-m-d H:i:s');

$due = db_all(
    "SELECT cq.id, cq.caption, cq.asset_id, cq.is_test, a.project_id, a.content_type, p.customer_id
       FROM content_queue cq
       JOIN assets   a ON a.id = cq.asset_id
       JOIN projects p ON p.id = a.project_id
      WHERE cq.platform = 'instagram'
        AND cq.status = 'approved'
        AND cq.published_at IS NULL
        AND cq.scheduled_at IS NOT NULL
        AND cq.scheduled_at <= ?
      ORDER BY cq.scheduled_at ASC
      LIMIT 10",
    [$nowBerlin]
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
        // Projekt auf 'gepostet' setzen (kein Test-Reel; nur vorwärts aus einem
        // freigegebenen/fertigen Zustand — Ideen/Archiv nicht anfassen).
        if (empty($row['is_test']) && !empty($row['project_id'])) {
            try {
                db_exec(
                    "UPDATE projects SET status='gepostet'
                      WHERE id=? AND status IN ('freigegeben','fertig','korrektur')",
                    [(string)$row['project_id']]
                );
            } catch (\Throwable $e) { /* ENUM evtl. noch ohne 'gepostet' — Migration nachziehen */ }
        }
        log_activity('content', (string)$row['asset_id'], 'published', ['via' => 'cron', 'ig_media' => $res['id']]);
        $results[] = ['id' => (int)$row['id'], 'ok' => true, 'mediaId' => $res['id']];
    } catch (\Throwable $e) {
        db_exec("UPDATE content_queue SET status='error', error_message=? WHERE id=?", [$e->getMessage(), $row['id']]);
        $results[] = ['id' => (int)$row['id'], 'ok' => false, 'error' => $e->getMessage()];
    }
}

json_ok(['processed' => count($results), 'results' => $results]);
