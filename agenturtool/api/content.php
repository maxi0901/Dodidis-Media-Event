<?php
declare(strict_types=1);

/**
 * Staff-API für die Social-Content-Freigabe (Session + CSRF, kein API-Key).
 * Listet Entwürfe aus content_queue und erlaubt Freigabe (mit geplanter Zeit),
 * Ablehnung, Bearbeitung und Löschen. Quelle der Entwürfe: n8n (content_create.php).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');
if (!has_role('admin', 'manager')) json_err(403, 'Keine Berechtigung.');

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;
$action = $_GET['action'] ?? '';
if (in_array($method, ['POST','PUT','DELETE'], true)) require_csrf();

$baseSelect = "SELECT cq.id, cq.customer_id AS customerId, c.name AS customerName,
                      cq.platform, cq.content_type AS contentType, cq.caption, cq.media_url AS mediaUrl,
                      cq.status, cq.approved_by AS approvedBy, cq.scheduled_at AS scheduledAt,
                      cq.published_at AS publishedAt, cq.platform_response AS platformResponse,
                      cq.error_message AS errorMessage, cq.created_at AS createdAt, cq.updated_at AS updatedAt
                 FROM content_queue cq
            LEFT JOIN customers c ON c.id = cq.customer_id";

// Hilfsfunktion: scheduledAt aus Body parsen (akzeptiert datetime-local & übliche Formate).
$parseScheduled = static function ($v): ?string {
    if (!$v) return null;
    $ts = strtotime((string)$v);
    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
};

switch ($method) {
    case 'GET': {
        if ($id) {
            $row = db_one("$baseSelect WHERE cq.id = ?", [(int)$id]);
            if (!$row) json_err(404, 'Nicht gefunden.');
            $row['id'] = (int)$row['id'];
            json_ok($row);
        }
        // Filter: ?status=draft|approved|published|error|rejected ; ?all=1 = alles
        $where = '';
        $params = [];
        if (!empty($_GET['status'])) {
            $where = " WHERE cq.status = ?";
            $params[] = (string)$_GET['status'];
        } elseif (empty($_GET['all'])) {
            // Default-Ansicht: offene Entwürfe + bereits freigegebene/veröffentlichte zur Kontrolle
            $where = " WHERE cq.status IN ('draft','approved','published','error','rejected')";
        }
        $rows = db_all("$baseSelect$where ORDER BY cq.created_at DESC", $params);
        foreach ($rows as &$r) { $r['id'] = (int)$r['id']; }
        json_ok($rows);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        $cq = db_one("SELECT id, status FROM content_queue WHERE id = ?", [(int)$id]);
        if (!$cq) json_err(404, 'Nicht gefunden.');
        $b = input_json();

        if ($action === 'approve') {
            $scheduled = $parseScheduled($b['scheduledAt'] ?? null);
            if (!$scheduled) json_err(400, 'Geplante Zeit (scheduledAt) ist Pflicht.');
            db_exec(
                "UPDATE content_queue
                    SET status='approved', approved_by=?, scheduled_at=?, updated_at=NOW()
                  WHERE id=?",
                [$session['uid'], $scheduled, (int)$id]
            );
            log_activity('content', (string)$id, 'approved', ['scheduledAt' => $scheduled]);
        } elseif ($action === 'reject') {
            db_exec("UPDATE content_queue SET status='rejected', updated_at=NOW() WHERE id=?", [(int)$id]);
            log_activity('content', (string)$id, 'rejected');
        } else {
            // Bearbeiten: caption / customerId / scheduledAt optional
            $set = []; $vals = [];
            if (array_key_exists('caption', $b))    { $set[] = 'caption = ?';     $vals[] = $b['caption'] !== null ? (string)$b['caption'] : null; }
            if (array_key_exists('customerId', $b)) { $set[] = 'customer_id = ?'; $vals[] = $b['customerId'] ?: null; }
            if (array_key_exists('scheduledAt', $b)){ $set[] = 'scheduled_at = ?';$vals[] = $parseScheduled($b['scheduledAt'] ?? null); }
            if ($set) {
                $set[] = 'updated_at = NOW()';
                $vals[] = (int)$id;
                db_exec("UPDATE content_queue SET " . implode(', ', $set) . " WHERE id = ?", $vals);
            }
            log_activity('content', (string)$id, 'updated');
        }

        $row = db_one("$baseSelect WHERE cq.id = ?", [(int)$id]);
        $row['id'] = (int)$row['id'];
        json_ok($row);
    }

    case 'DELETE': {
        require_role('admin');
        if (!$id) json_err(400, 'id fehlt.');
        db_exec("DELETE FROM content_queue WHERE id = ?", [(int)$id]);
        log_activity('content', (string)$id, 'deleted');
        json_ok(['id' => (int)$id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
