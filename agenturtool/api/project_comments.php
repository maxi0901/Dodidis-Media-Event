<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');

$method    = $_SERVER['REQUEST_METHOD'];
$projectId = $_GET['projectId'] ?? null;
$id        = $_GET['id']        ?? null;

if (in_array($method, ['POST', 'DELETE'], true)) require_csrf();

if ($method === 'GET') {
    if (!$projectId) json_err(400, 'projectId fehlt.');
    $rows = db_all(
        "SELECT id, project_id AS projectId, user_id AS userId,
                comment_text AS text,
                (voice_path IS NOT NULL AND voice_path != '') AS hasVoice,
                created_at AS createdAt
           FROM project_comments
          WHERE project_id = ?
          ORDER BY created_at ASC",
        [$projectId]
    );
    foreach ($rows as &$r) {
        $r['hasVoice'] = (bool)$r['hasVoice'];
    }
    unset($r);
    json_ok($rows);
}

if ($method === 'POST') {
    if (!$projectId) json_err(400, 'projectId fehlt.');
    $proj = db_one("SELECT id, title, cutter_id FROM projects WHERE id = ?", [$projectId]);
    if (!$proj) json_err(404, 'Projekt nicht gefunden.');

    $b    = input_json();
    $text = s($b['text'] ?? null, 4000);
    $newId = uid('pc');

    db_exec(
        "INSERT INTO project_comments (id, project_id, user_id, comment_text) VALUES (?, ?, ?, ?)",
        [$newId, $projectId, $session['uid'], $text]
    );
    log_activity('project', $projectId, 'commented');

    // Cutter benachrichtigen
    if (!empty($proj['cutter_id']) && $proj['cutter_id'] !== $session['uid']) {
        try {
            db_exec(
                "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type)
                 VALUES (?, 'project_comment', 'Korrektur-Hinweis', ?, ?, 'project')",
                [$proj['cutter_id'], $proj['title'], $projectId]
            );
        } catch (\Throwable $e) {
            error_log('[project_comments] notification: ' . $e->getMessage());
        }
    }

    $row = db_one(
        "SELECT id, project_id AS projectId, user_id AS userId,
                comment_text AS text,
                (voice_path IS NOT NULL AND voice_path != '') AS hasVoice,
                created_at AS createdAt
           FROM project_comments WHERE id = ?",
        [$newId]
    );
    $row['hasVoice'] = (bool)$row['hasVoice'];
    json_ok($row, 201);
}

if ($method === 'DELETE') {
    if (!$id) json_err(400, 'id fehlt.');
    if (!has_role('admin', 'manager')) json_err(403, 'Keine Berechtigung.');
    $row = db_one("SELECT project_id, voice_path FROM project_comments WHERE id = ?", [$id]);
    if (!$row) json_err(404, 'Kommentar nicht gefunden.');

    // Sprachdatei löschen wenn vorhanden
    if (!empty($row['voice_path'])) {
        try {
            $cfg = require __DIR__ . '/../config.php';
            $fsPath = $cfg['private_path'] . '/' . $row['voice_path'];
            if (file_exists($fsPath)) @unlink($fsPath);
        } catch (\Throwable $e) {
            error_log('[project_comments] file delete: ' . $e->getMessage());
        }
    }

    db_exec("DELETE FROM project_comments WHERE id = ?", [$id]);
    log_activity('project', $row['project_id'], 'commentDeleted');
    json_ok(['id' => $id]);
}

json_err(405, 'Methode nicht erlaubt.');
