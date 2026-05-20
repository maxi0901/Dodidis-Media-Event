<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = $_GET['id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

if (($session['type'] ?? '') === 'customer') {
    json_err(403, 'Keine Berechtigung.');
}

$baseSelect = "SELECT id, title, description, created_by_id AS createdById,
                      due_date AS dueDate, status,
                      created_at AS createdAt, updated_at AS updatedAt
                 FROM todos";

switch ($method) {

    case 'GET': {
        $uid = $session['uid'];
        if ($id) {
            $t = db_one("$baseSelect WHERE id = ?", [$id]);
            if (!$t) json_err(404, 'Todo nicht gefunden.');
            hydrate_todo_join($t);
            if (!has_role('admin', 'manager') && !in_array($uid, $t['assigneeIds'], true) && $t['createdById'] !== $uid) {
                json_err(403, 'Keine Berechtigung.');
            }
            json_ok($t);
        }

        if (has_role('admin', 'manager')) {
            $rows = db_all("$baseSelect ORDER BY status='done', COALESCE(due_date, created_at)");
        } else {
            // Assignee oder Ersteller
            $rows = db_all(
                "$baseSelect WHERE id IN (
                    SELECT todo_id FROM todo_assignees WHERE user_id = ?
                ) OR created_by_id = ?
                 ORDER BY status='done', COALESCE(due_date, created_at)",
                [$uid, $uid]
            );
        }
        hydrate_todos_join($rows);
        json_ok($rows);
    }

    case 'POST': {
        require_role('admin', 'manager');
        $b = input_json();
        $title = s($b['title'] ?? null, 255);
        if (!$title) json_err(400, 'title ist Pflicht.');
        $newId = $b['id'] ?? uid('t');

        $assignees = array_values(array_filter((array)($b['assigneeIds'] ?? []), 'is_string'));
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO todos (id, title, description, created_by_id, due_date, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            )->execute([
                $newId,
                $title,
                $b['description'] !== null ? (string)$b['description'] : null,
                $session['uid'],
                as_date($b['dueDate'] ?? null),
                in_array($b['status'] ?? 'open', ['open','done'], true) ? $b['status'] : 'open',
            ]);
            sync_assignees($newId, $assignees);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            log_err('todo create', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte Aufgabe nicht anlegen.');
        }
        log_activity('todo', $newId, 'created');
        $row = db_one("$baseSelect WHERE id = ?", [$newId]);
        hydrate_todo_join($row);
        json_ok($row, 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        $cur = db_one("SELECT id, created_by_id FROM todos WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Todo nicht gefunden.');

        $b      = input_json();
        $uid    = $session['uid'];
        $isPriv = has_role('admin', 'manager');
        $isAssignee = (bool)db_one("SELECT 1 AS ok FROM todo_assignees WHERE todo_id = ? AND user_id = ?", [$id, $uid]);

        if (!$isPriv && !$isAssignee && $cur['created_by_id'] !== $uid) {
            json_err(403, 'Keine Berechtigung.');
        }

        // Nicht-Privilegierte dürfen nur status und seenBy ändern
        if (!$isPriv) {
            $b = array_intersect_key($b, ['status' => 1, 'seenBy' => 1, 'markSeen' => 1]);
        }

        $set = [];
        $vals = [];
        if (array_key_exists('title', $b))       { $set[] = 'title = ?';       $vals[] = s($b['title'], 255); }
        if (array_key_exists('description', $b)) { $set[] = 'description = ?'; $vals[] = $b['description'] !== null ? (string)$b['description'] : null; }
        if (array_key_exists('dueDate', $b))     { $set[] = 'due_date = ?';    $vals[] = as_date($b['dueDate']); }
        if (array_key_exists('status', $b)) {
            $st = $b['status'];
            if (!in_array($st, ['open','done'], true)) json_err(400, 'Ungültiger Status.');
            $set[] = 'status = ?'; $vals[] = $st;
        }

        if ($set) {
            $vals[] = $id;
            db_exec("UPDATE todos SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        }

        if (array_key_exists('assigneeIds', $b) && $isPriv) {
            db_exec("DELETE FROM todo_assignees WHERE todo_id = ?", [$id]);
            sync_assignees($id, (array)$b['assigneeIds']);
        }
        if (!empty($b['markSeen'])) {
            db()->prepare("INSERT IGNORE INTO todo_seen (todo_id, user_id) VALUES (?, ?)")
                ->execute([$id, $uid]);
        }

        log_activity('todo', $id, 'edited');
        $row = db_one("$baseSelect WHERE id = ?", [$id]);
        hydrate_todo_join($row);
        json_ok($row);
    }

    case 'DELETE': {
        require_role('admin', 'manager');
        if (!$id) json_err(400, 'id fehlt.');
        db_exec("DELETE FROM todos WHERE id = ?", [$id]);
        log_activity('todo', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}

// ---------------------------------------------------------------------------

function sync_assignees(string $todoId, array $userIds): void
{
    $stmt = db()->prepare("INSERT IGNORE INTO todo_assignees (todo_id, user_id) VALUES (?, ?)");
    foreach (array_unique(array_filter($userIds, 'is_string')) as $u) {
        $stmt->execute([$todoId, $u]);
    }
}

function hydrate_todo_join(array &$todo): void
{
    $a = db_all("SELECT user_id FROM todo_assignees WHERE todo_id = ?", [$todo['id']]);
    $s = db_all("SELECT user_id FROM todo_seen WHERE todo_id = ?", [$todo['id']]);
    $todo['assigneeIds'] = array_column($a, 'user_id');
    $todo['seenBy']      = array_column($s, 'user_id');
}

function hydrate_todos_join(array &$todos): void
{
    if (!$todos) return;
    $ids = array_column($todos, 'id');
    $place = implode(',', array_fill(0, count($ids), '?'));
    $a = db_all("SELECT todo_id, user_id FROM todo_assignees WHERE todo_id IN ($place)", $ids);
    $s = db_all("SELECT todo_id, user_id FROM todo_seen       WHERE todo_id IN ($place)", $ids);
    $byA = []; $byS = [];
    foreach ($a as $r) $byA[$r['todo_id']][] = $r['user_id'];
    foreach ($s as $r) $byS[$r['todo_id']][] = $r['user_id'];
    foreach ($todos as &$t) {
        $t['assigneeIds'] = $byA[$t['id']] ?? [];
        $t['seenBy']      = $byS[$t['id']] ?? [];
    }
    unset($t);
}
