<?php
declare(strict_types=1);

/**
 * Posting-Planer-API (Staff, Session + CSRF).
 *
 * Bindeglied zwischen fertigen Reels (assets/projects) und der bestehenden
 * content_queue, die n8n abarbeitet. Ein geplanter Post = EINE content_queue-
 * Zeile pro Plattform (mehrere Plattformen → mehrere Zeilen, gemeinsame
 * asset_id). Die Publish-Kette (content_pending/published/error) bleibt
 * unverändert; hier entstehen nur die Einträge.
 *
 * Routen:
 *   GET  ?action=pool[&only_ready=1]  — planbare Reels (freigegeben, mit Video)
 *   GET  ?action=scheduled[&from=&to=] — geplante/veröffentlichte Posts (Kalender)
 *   POST ?action=plan                  — Reel auf Datum/Uhrzeit einplanen
 *   PUT  ?id=                          — geplanten Post ändern (Zeit/Caption/…)
 *   DELETE ?id=                        — geplanten Post entfernen (nur wenn nicht veröffentlicht)
 *
 * Diese API ist bewusst noch NICHT in die UI eingebunden (Groundwork).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';
$id      = $_GET['id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}
if (($session['type'] ?? '') === 'customer' || !has_role('admin', 'manager')) {
    json_err(403, 'Nur Manager oder Admins dürfen den Posting-Planer nutzen.');
}

// Sichtbarkeits-Scope: Admin sieht alles; ein reiner Manager nur Kunden, die
// ihm zugewiesen sind (customers.manager_id) — analog zur Board-Einschränkung.
$isAdmin  = has_role('admin');
$mgrScope = (!$isAdmin && has_role('manager')) ? (string)$session['uid'] : null;

/** Stellt sicher, dass ein Manager nur auf seine eigenen Kunden zugreift. */
$assertCustomerScope = static function (?string $customerId) use ($mgrScope): void {
    if ($mgrScope === null) return; // Admin: keine Einschränkung
    if (!$customerId) json_err(403, 'Kein Zugriff auf diesen Eintrag.');
    $c = db_one("SELECT manager_id FROM customers WHERE id = ?", [$customerId]);
    if (!$c || (string)($c['manager_id'] ?? '') !== $mgrScope) {
        json_err(403, 'Nur auf zugewiesene Kunden zugreifbar.');
    }
};

// Erlaubte Plattformen/Typen (auswählbar im Planer). Facebook kann nativ
// vorgeplant werden, Instagram wird termingesteuert von uns veröffentlicht.
const PLAN_PLATFORMS = ['instagram', 'facebook', 'tiktok', 'youtube', 'linkedin'];
const PLAN_TYPES     = ['reel', 'story', 'post', 'short', 'video'];

$parseWhen = static function ($v): ?string {
    if (!$v) return null;
    $ts = strtotime((string)$v);
    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
};

// Absolute URL zur App (Basis = zwei Ebenen über dieser Datei: /…/api/x.php → /…)
$absUrl = static function (string $rel): string {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appPath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x'))), '/');
    return $scheme . '://' . $host . $appPath . '/' . ltrim($rel, '/');
};

switch ($method) {

    // ── GET ?action=pool — planbare Reels ────────────────────────────────────
    case 'GET': {
        if ($action === 'pool') {
            // „nur fertige" = freigegebene Projekte; sonst alle mit finalem Video.
            // Der Planer zeigt standardmäßig freigegebene, kann aber (Filter) alle
            // Reels anzeigen (Wunsch aus der Besprechung: nicht nur fertige planbar).
            $onlyReady = !empty($_GET['only_ready']);
            // „Fertige" = freigegebene (posting-fertig). „Alle" = alle mit finalem
            // Video, die NOCH NICHT freigegeben (und nicht schon gepostet/archiviert)
            // sind — freigegebene erscheinen unter „Alle" bewusst nicht.
            $statusCond = $onlyReady
                ? "p.status = 'freigegeben'"
                : "p.status IN ('fertig','korrektur')";

            $scopeCond = $mgrScope !== null ? " AND c.manager_id = ?" : '';
            $scopeParams = $mgrScope !== null ? [$mgrScope] : [];
            $rows = db_all(
                "SELECT a.id AS assetId, a.project_id AS projectId, a.filename,
                        a.content_type AS contentType, a.size_bytes AS sizeBytes,
                        p.title AS projectTitle, p.status AS projectStatus,
                        p.customer_id AS customerId, c.name AS customerName,
                        (SELECT COUNT(*) FROM content_queue cq
                          WHERE cq.asset_id = a.id AND cq.status <> 'rejected') AS plannedCount
                   FROM assets a
                   JOIN projects p  ON p.id = a.project_id
              LEFT JOIN customers c ON c.id = p.customer_id
                  WHERE a.kind = 'final' AND a.status = 'stored'
                    AND NOT EXISTS (SELECT 1 FROM content_queue cqp
                                     WHERE cqp.asset_id = a.id AND cqp.status = 'published')
                    AND {$statusCond}{$scopeCond}
                  ORDER BY p.status = 'freigegeben' DESC, a.created_at DESC",
                $scopeParams
            );
            foreach ($rows as &$r) {
                $r['sizeBytes']    = $r['sizeBytes'] !== null ? (int)$r['sizeBytes'] : null;
                $r['plannedCount'] = (int)$r['plannedCount'];
            }
            json_ok($rows);
        }

        // ── GET ?action=scheduled — Kalendereinträge ─────────────────────────
        if ($action === 'scheduled') {
            $where  = "cq.status IN ('draft','approved','published','error')";
            $params = [];
            if (!empty($_GET['from'])) { $where .= " AND cq.scheduled_at >= ?"; $params[] = ($parseWhen($_GET['from']) ?? $_GET['from']); }
            if (!empty($_GET['to']))   { $where .= " AND cq.scheduled_at <= ?"; $params[] = ($parseWhen($_GET['to'])   ?? $_GET['to']); }
            if ($mgrScope !== null)    { $where .= " AND c.manager_id = ?";     $params[] = $mgrScope; }

            $rows = db_all(
                "SELECT cq.id, cq.customer_id AS customerId, c.name AS customerName,
                        cq.asset_id AS assetId, cq.project_id AS projectId,
                        cq.platform, cq.content_type AS contentType,
                        cq.caption, cq.media_url AS mediaUrl, cq.thumbnail_url AS thumbnailUrl,
                        cq.status, cq.scheduled_at AS scheduledAt, cq.published_at AS publishedAt,
                        cq.is_test AS isTest,
                        p.title AS projectTitle, p.status AS projectStatus
                   FROM content_queue cq
              LEFT JOIN customers c ON c.id = cq.customer_id
              LEFT JOIN projects  p ON p.id = cq.project_id
                  WHERE {$where}
                  ORDER BY cq.scheduled_at ASC",
                $params
            );
            foreach ($rows as &$r) { $r['id'] = (int)$r['id']; $r['isTest'] = (int)($r['isTest'] ?? 0); }
            json_ok($rows);
        }

        json_err(400, "Unbekannte action. Erlaubt: pool, scheduled.");
    }

    // ── POST ?action=plan — Reel einplanen ───────────────────────────────────
    case 'POST': {
        if ($action !== 'plan') json_err(400, "Unbekannte action. Erlaubt: plan.");
        $b = input_json();

        $assetId   = trim((string)($b['asset_id'] ?? ''));
        $platforms = array_values(array_unique(array_filter((array)($b['platforms'] ?? []), 'is_string')));
        $type      = trim((string)($b['content_type'] ?? 'reel')) ?: 'reel';
        $caption   = isset($b['caption']) ? (string)$b['caption'] : null;
        $thumb     = isset($b['thumbnail_url']) ? (string)$b['thumbnail_url'] : null;
        $when      = $parseWhen($b['scheduled_at'] ?? null);
        $isTest    = !empty($b['is_test']);
        $delayH    = isset($b['delay_hours']) ? (int)$b['delay_hours'] : 0;

        // Test-Reel: Auto-Posting nach 24/48 h. scheduled_at wird aus „jetzt + N h"
        // berechnet (Europe/Berlin, exakt wie publish_due.php die Fälligkeit prüft)
        // und ersetzt eine manuell gewählte Zeit.
        if ($delayH > 0) {
            if (!in_array($delayH, [24, 48], true)) json_err(400, 'delay_hours muss 24 oder 48 sein.');
            try {
                $when = (new DateTime('now', new DateTimeZone('Europe/Berlin')))
                            ->modify("+{$delayH} hours")->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                json_err(500, 'Zeitberechnung fehlgeschlagen.');
            }
        }

        if ($assetId === '')  json_err(400, 'asset_id ist Pflicht.');
        if (!$platforms)      json_err(400, 'Mindestens eine Plattform wählen.');
        if (!$when)           json_err(400, 'Datum + Uhrzeit (scheduled_at) ist Pflicht.');
        foreach ($platforms as $pf) {
            if (!in_array($pf, PLAN_PLATFORMS, true)) json_err(400, "Unbekannte Plattform: {$pf}");
        }
        if (!in_array($type, PLAN_TYPES, true)) json_err(400, "Unbekannter Content-Typ: {$type}");

        // Asset muss ein gespeicherter finaler Schnitt sein
        $a = db_one(
            "SELECT a.id, a.project_id, a.nas_key, p.customer_id, p.status AS projectStatus
               FROM assets a JOIN projects p ON p.id = a.project_id
              WHERE a.id = ? AND a.kind = 'final' AND a.status = 'stored'",
            [$assetId]
        );
        if (!$a) json_err(404, 'Finaler Schnitt nicht gefunden.');
        $assertCustomerScope($a['customer_id'] ?? null); // Manager: nur eigene Kunden

        // Auto-Freigabe NUR bei freigegebenen Projekten: nur dann geht der Post
        // ohne separaten Content-Review automatisch raus (Planen = Posten).
        // Reels aus fertig/korrektur (über „Alle" planbar) bleiben 'draft' und
        // müssen erst im Content-Review freigegeben werden.
        $queueStatus = (($a['projectStatus'] ?? '') === 'freigegeben') ? 'approved' : 'draft';

        // media_url = absolute URL zum finalen Schnitt. Hinweis für Stufe C:
        // dieser Download verlangt aktuell eine Staff-Session. Für n8n/Meta wird
        // dann ein API-Key-geschützter Media-Endpoint ergänzt (oder n8n zieht
        // direkt vom NAS). Bis dahin dient die URL als stabile Referenz.
        $mediaUrl = $absUrl('api/nas_assets.php?id=' . rawurlencode($assetId));

        $created = [];
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO content_queue
                   (customer_id, platform, content_type, caption, media_url, thumbnail_url,
                    asset_id, project_id, planned_by, status, scheduled_at, is_test)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($platforms as $pf) {
                $stmt->execute([
                    $a['customer_id'], $pf, $type, $caption, $mediaUrl, $thumb,
                    $assetId, $a['project_id'], $session['uid'], $queueStatus, $when, $isTest ? 1 : 0,
                ]);
                $created[] = (int)$pdo->lastInsertId();
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            log_err('content_plan', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte Planung nicht speichern.');
        }

        log_activity('content_plan', $assetId, 'planned',
            ['platforms' => $platforms, 'when' => $when, 'ids' => $created]);
        json_ok(['ids' => $created, 'count' => count($created)], 201);
    }

    // ── PUT ?id= — geplanten Post ändern ─────────────────────────────────────
    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        $cq = db_one("SELECT id, status, customer_id FROM content_queue WHERE id = ?", [(int)$id]);
        if (!$cq) json_err(404, 'Geplanter Post nicht gefunden.');
        $assertCustomerScope($cq['customer_id'] ?? null); // Manager: nur eigene Kunden
        if ($cq['status'] === 'published') json_err(409, 'Bereits veröffentlicht — nicht mehr änderbar.');

        $b = input_json();
        $set = []; $vals = [];
        if (array_key_exists('caption', $b))      { $set[] = 'caption = ?';       $vals[] = $b['caption'] !== null ? (string)$b['caption'] : null; }
        if (array_key_exists('thumbnail_url', $b)){ $set[] = 'thumbnail_url = ?'; $vals[] = $b['thumbnail_url'] !== null ? (string)$b['thumbnail_url'] : null; }
        if (array_key_exists('scheduled_at', $b)) {
            $when = $parseWhen($b['scheduled_at'] ?? null);
            if (!$when) json_err(400, 'Ungültige Zeit.');
            $set[] = 'scheduled_at = ?'; $vals[] = $when;
        }
        if (array_key_exists('content_type', $b)) {
            $t = (string)$b['content_type'];
            if (!in_array($t, PLAN_TYPES, true)) json_err(400, "Unbekannter Content-Typ: {$t}");
            $set[] = 'content_type = ?'; $vals[] = $t;
        }
        if (!$set) json_ok(['id' => (int)$id]);

        $set[] = 'updated_at = NOW()';
        $vals[] = (int)$id;
        db_exec("UPDATE content_queue SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        log_activity('content_plan', (string)$id, 'updated');
        json_ok(['id' => (int)$id]);
    }

    // ── DELETE ?id= — Planung entfernen ──────────────────────────────────────
    case 'DELETE': {
        if (!$id) json_err(400, 'id fehlt.');
        $cq = db_one("SELECT id, status, customer_id FROM content_queue WHERE id = ?", [(int)$id]);
        if (!$cq) json_err(404, 'Geplanter Post nicht gefunden.');
        $assertCustomerScope($cq['customer_id'] ?? null); // Manager: nur eigene Kunden
        if ($cq['status'] === 'published') json_err(409, 'Bereits veröffentlicht — kann nicht entfernt werden.');
        db_exec("DELETE FROM content_queue WHERE id = ?", [(int)$id]);
        log_activity('content_plan', (string)$id, 'unplanned');
        json_ok(['id' => (int)$id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
