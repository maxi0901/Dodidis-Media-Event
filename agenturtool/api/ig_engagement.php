<?php
declare(strict_types=1);

/**
 * Instagram-Engagement (Phase 1): Insights + Kommentare zu veröffentlichten Posts.
 *
 *   GET  ?action=posts                 — veröffentlichte IG-Posts (DB, schnell)
 *   GET  ?action=stats&id=<cq_id>      — Live-Zahlen (Views/Reach/Likes/Kommentare)
 *   GET  ?action=comments&id=<cq_id>   — Kommentare inkl. Antworten
 *   POST ?action=reply {id, comment_id, message} — auf Kommentar antworten
 *
 * Nur Admin/Manager (Manager nur eigene Kunden). Token je Kunde aus social_accounts.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/MetaClient.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';

if ($method === 'POST') require_csrf();
if (($session['type'] ?? '') === 'customer' || !has_role('admin', 'manager')) {
    json_err(403, 'Nur Admin/Manager.');
}

$isAdmin  = has_role('admin');
$mgrScope = (!$isAdmin && has_role('manager')) ? (string)$session['uid'] : null;

/**
 * Löst zu einer content_queue-ID die IG-Media-ID + das Zugriffstoken des Kunden auf.
 * Prüft dabei den Manager-Scope. Beendet mit json_err bei Fehler.
 * @return array{mediaId:string, token:string}
 */
function resolve_ig(string $cqId, ?string $mgrScope): array
{
    $row = db_one(
        "SELECT cq.id, cq.customer_id, cq.platform_response,
                c.manager_id AS managerId,
                sa.external_id AS igId, sa.access_token AS token
           FROM content_queue cq
      LEFT JOIN customers c        ON c.id = cq.customer_id
      LEFT JOIN social_accounts sa ON sa.customer_id = cq.customer_id
                                  AND sa.platform = 'instagram' AND sa.status = 'connected'
          WHERE cq.id = ? AND cq.platform = 'instagram' AND cq.status = 'published'",
        [(int)$cqId]
    );
    if (!$row) json_err(404, 'Post nicht gefunden.');
    if ($mgrScope !== null && (string)($row['managerId'] ?? '') !== $mgrScope) {
        json_err(403, 'Nur eigene Kunden.');
    }
    if (empty($row['token'])) json_err(409, 'Für diesen Kunden ist kein Instagram-Konto verbunden.');

    $pr      = json_decode((string)$row['platform_response'], true);
    $mediaId = is_array($pr) ? (string)($pr['id'] ?? '') : '';
    if ($mediaId === '') json_err(409, 'Keine Media-ID zum Post gespeichert.');

    return ['mediaId' => $mediaId, 'token' => (string)$row['token']];
}

// ── GET ?action=posts ────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'posts') {
    $scope  = $mgrScope !== null ? " AND c.manager_id = ?" : '';
    $params = $mgrScope !== null ? [$mgrScope] : [];
    $rows = db_all(
        "SELECT cq.id, cq.caption, cq.published_at AS publishedAt, cq.platform_response AS platformResponse,
                cq.customer_id AS customerId, c.name AS customerName, p.title AS projectTitle
           FROM content_queue cq
      LEFT JOIN customers c ON c.id = cq.customer_id
      LEFT JOIN projects  p ON p.id = cq.project_id
          WHERE cq.platform = 'instagram' AND cq.status = 'published'{$scope}
          ORDER BY cq.published_at DESC
          LIMIT 30",
        $params
    );
    $out = [];
    foreach ($rows as $r) {
        $pr = json_decode((string)$r['platformResponse'], true);
        $out[] = [
            'id'           => (int)$r['id'],
            'caption'      => $r['caption'],
            'publishedAt'  => $r['publishedAt'],
            'customerName' => $r['customerName'],
            'projectTitle' => $r['projectTitle'],
            'mediaId'      => is_array($pr) ? ($pr['id'] ?? null) : null,
            'permalink'    => is_array($pr) ? ($pr['permalink'] ?? null) : null,
        ];
    }
    json_ok($out);
}

// ── GET ?action=stats ────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'stats') {
    $r = resolve_ig((string)($_GET['id'] ?? ''), $mgrScope);
    try {
        json_ok((new MetaClient('x'))->getMediaStats($r['mediaId'], $r['token']));
    } catch (\Throwable $e) {
        json_err(502, 'Instagram-Statistik fehlgeschlagen: ' . $e->getMessage());
    }
}

// ── GET ?action=comments ─────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'comments') {
    $r = resolve_ig((string)($_GET['id'] ?? ''), $mgrScope);
    try {
        json_ok(['comments' => (new MetaClient('x'))->listComments($r['mediaId'], $r['token'])]);
    } catch (\Throwable $e) {
        json_err(502, 'Kommentare laden fehlgeschlagen: ' . $e->getMessage());
    }
}

// ── POST ?action=reply ───────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'reply') {
    $body      = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) $body = [];
    $cqId      = (string)($body['id'] ?? '');
    $commentId = trim((string)($body['comment_id'] ?? ''));
    $message   = trim((string)($body['message'] ?? ''));
    if ($commentId === '' || $message === '') json_err(400, 'comment_id und message sind Pflicht.');

    $r = resolve_ig($cqId, $mgrScope);
    try {
        $res = (new MetaClient('x'))->replyToComment($commentId, $r['token'], $message);
        log_activity('ig_comment', $commentId, 'replied', ['reply' => $res['id'] ?? null]);
        json_ok(['replyId' => $res['id'] ?? null]);
    } catch (\Throwable $e) {
        json_err(502, 'Antwort fehlgeschlagen: ' . $e->getMessage());
    }
}

json_err(400, 'Unbekannte action.');
