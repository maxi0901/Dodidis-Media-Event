<?php
declare(strict_types=1);

/**
 * Timestamp-Kommentare für Review-Assets (finale Schnitte).
 *
 * Rollen:
 *   - Kommentieren:        Videograf, Manager, Admin
 *   - Abhaken (resolve):   Cutter, Manager, Admin
 *   - Löschen:             Autor selbst, Manager, Admin
 *
 * Auto-Status: erster neuer Kommentar auf ein Projekt im Status 'fertig'
 * setzt es auf 'korrektur' — der Cutter sieht sofort, dass es Änderungs-
 * wünsche gibt.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = $_GET['id']       ?? null;
$action  = $_GET['action']   ?? null;
$assetId = $_GET['asset_id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen Review-Kommentare sehen.');
}

// ── GET ?asset_id= — Kommentare eines Assets ─────────────────────────────────
if ($method === 'GET' && $assetId) {
    $a = db_one("SELECT project_id FROM assets WHERE id = ?", [$assetId]);
    if (!$a) json_err(404, 'Asset nicht gefunden.');
    requireProjectAccess((string)$a['project_id'], $session);

    $rows = db_all(
        "SELECT c.id, c.asset_id AS assetId, c.user_id AS userId,
                u.name AS userName,
                c.timecode, c.body, c.status,
                c.resolved_by AS resolvedBy, c.resolved_at AS resolvedAt,
                c.created_at AS createdAt
           FROM asset_comments c
           LEFT JOIN users u ON u.id = c.user_id
          WHERE c.asset_id = ?
          ORDER BY COALESCE(c.timecode, 999999), c.created_at",
        [$assetId]
    );
    json_ok($rows);
}

// ── POST (ohne id) — Kommentar anlegen ───────────────────────────────────────
if ($method === 'POST' && !$id) {
    if (!has_role('admin', 'manager', 'videograf')) {
        json_err(403, 'Kommentieren dürfen nur Videografen, Manager oder Admins.');
    }

    $b       = input_json();
    $assetId = trim((string)($b['asset_id'] ?? ''));
    $body    = trim((string)($b['body'] ?? ''));
    $tc      = $b['timecode'] ?? null;

    if ($assetId === '') json_err(400, 'asset_id ist Pflicht.');
    if ($body === '')    json_err(400, 'Kommentartext fehlt.');
    if (mb_strlen($body) > 2000) json_err(400, 'Kommentar zu lang (max. 2000 Zeichen).');

    $a = db_one("SELECT id, project_id, kind FROM assets WHERE id = ? AND status = 'stored'", [$assetId]);
    if (!$a) json_err(404, 'Asset nicht gefunden.');
    requireProjectAccess((string)$a['project_id'], $session);

    $timecode = null;
    if ($tc !== null && $tc !== '') {
        $timecode = max(0, round((float)$tc, 2));
    }

    $cid = uid('ac');
    db_exec(
        "INSERT INTO asset_comments (id, asset_id, project_id, user_id, timecode, body)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$cid, $assetId, $a['project_id'], $session['uid'], $timecode, $body]
    );

    // Auto-Status: fertig → korrektur (nur vorwärts, nie überschreiben)
    try {
        db_exec(
            "UPDATE projects SET status = 'korrektur' WHERE id = ? AND status = 'fertig'",
            [$a['project_id']]
        );
    } catch (\Throwable $e) {
        error_log('[nas_comments] Auto-Status korrektur: ' . $e->getMessage());
    }

    log_activity('asset_comment', $cid, 'created', ['asset' => $assetId]);
    json_ok(comment_doc($cid), 201);
}

// ── POST ?id=&action=resolve|reopen — Abhaken / wieder öffnen ────────────────
if ($method === 'POST' && $id && in_array($action, ['resolve', 'reopen'], true)) {
    $c = db_one("SELECT * FROM asset_comments WHERE id = ?", [$id]);
    if (!$c) json_err(404, 'Kommentar nicht gefunden.');
    requireProjectAccess((string)$c['project_id'], $session);

    if (!has_role('admin', 'manager', 'cutter')) {
        json_err(403, 'Abhaken dürfen nur Cutter, Manager oder Admins.');
    }

    if ($action === 'resolve') {
        db_exec(
            "UPDATE asset_comments SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?",
            [$session['uid'], $id]
        );
    } else {
        db_exec(
            "UPDATE asset_comments SET status = 'open', resolved_by = NULL, resolved_at = NULL WHERE id = ?",
            [$id]
        );
    }
    log_activity('asset_comment', $id, $action);
    json_ok(comment_doc($id));
}

// ── DELETE ?id= ───────────────────────────────────────────────────────────────
if ($method === 'DELETE' && $id) {
    $c = db_one("SELECT * FROM asset_comments WHERE id = ?", [$id]);
    if (!$c) json_err(404, 'Kommentar nicht gefunden.');
    requireProjectAccess((string)$c['project_id'], $session);

    if ($c['user_id'] !== $session['uid'] && !has_role('admin', 'manager')) {
        json_err(403, 'Nur der Autor, Manager oder Admins dürfen Kommentare löschen.');
    }

    db_exec("DELETE FROM asset_comments WHERE id = ?", [$id]);
    log_activity('asset_comment', $id, 'deleted');
    json_ok(['id' => $id]);
}

json_err(400, 'Ungültige Anfrage. Benötigt asset_id=..., POST-Body, oder id=...&action=resolve|reopen.');

// ─────────────────────────────────────────────────────────────────────────────

function comment_doc(string $cid): array
{
    $c = db_one(
        "SELECT c.id, c.asset_id AS assetId, c.user_id AS userId,
                u.name AS userName,
                c.timecode, c.body, c.status,
                c.resolved_by AS resolvedBy, c.resolved_at AS resolvedAt,
                c.created_at AS createdAt
           FROM asset_comments c
           LEFT JOIN users u ON u.id = c.user_id
          WHERE c.id = ?",
        [$cid]
    );
    return $c ?: [];
}
