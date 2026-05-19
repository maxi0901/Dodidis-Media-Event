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

$cols = "id, user_id AS userId, start_date AS startDate, end_date AS endDate,
         note, approved_by AS approvedBy, approved_at AS approvedAt, created_at AS createdAt";

switch ($method) {

    case 'GET': {
        if ($id) {
            $v = db_one("SELECT $cols FROM vacations WHERE id = ?", [$id]);
            if (!$v) json_err(404, 'Urlaub nicht gefunden.');
            json_ok($v);
        }
        // Volle Transparenz für alle Mitarbeiter
        $rows = db_all("SELECT $cols FROM vacations ORDER BY start_date DESC");
        json_ok($rows);
    }

    case 'POST': {
        $b = input_json();
        $userId    = (string)($b['userId'] ?? $session['uid']);
        $startDate = as_date($b['startDate'] ?? null);
        $endDate   = as_date($b['endDate']   ?? null);
        if (!$startDate || !$endDate) json_err(400, 'startDate und endDate sind Pflicht.');
        if (strcmp($endDate, $startDate) < 0) json_err(400, 'endDate liegt vor startDate.');

        if (!has_role('admin') && $userId !== $session['uid']) {
            json_err(403, 'Du kannst nur eigenen Urlaub anlegen.');
        }

        $newId = $b['id'] ?? uid('v');
        db_exec(
            "INSERT INTO vacations (id, user_id, start_date, end_date, note) VALUES (?, ?, ?, ?, ?)",
            [$newId, $userId, $startDate, $endDate, s($b['note'] ?? null, 255)]
        );
        log_activity('vacation', $newId, 'created', ['user' => $userId]);
        json_ok(db_one("SELECT $cols FROM vacations WHERE id = ?", [$newId]), 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        $cur = db_one("SELECT user_id FROM vacations WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Urlaub nicht gefunden.');
        if (!has_role('admin') && $cur['user_id'] !== $session['uid']) {
            json_err(403, 'Keine Berechtigung.');
        }
        $b = input_json();
        $set = [];
        $vals = [];
        if (array_key_exists('startDate', $b)) { $set[] = 'start_date = ?'; $vals[] = as_date($b['startDate']); }
        if (array_key_exists('endDate',   $b)) { $set[] = 'end_date = ?';   $vals[] = as_date($b['endDate']); }
        if (array_key_exists('note',      $b)) { $set[] = 'note = ?';       $vals[] = s($b['note'], 255); }
        if (array_key_exists('approvedBy', $b) && has_role('admin')) {
            $set[] = 'approved_by = ?'; $vals[] = $b['approvedBy'] ?: null;
            $set[] = 'approved_at = ?'; $vals[] = $b['approvedBy'] ? date('Y-m-d H:i:s') : null;
        }
        if ($set) {
            $vals[] = $id;
            db_exec("UPDATE vacations SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        }
        log_activity('vacation', $id, 'edited');
        json_ok(db_one("SELECT $cols FROM vacations WHERE id = ?", [$id]));
    }

    case 'DELETE': {
        if (!$id) json_err(400, 'id fehlt.');
        $cur = db_one("SELECT user_id FROM vacations WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Urlaub nicht gefunden.');
        if (!has_role('admin') && $cur['user_id'] !== $session['uid']) {
            json_err(403, 'Keine Berechtigung.');
        }
        db_exec("DELETE FROM vacations WHERE id = ?", [$id]);
        log_activity('vacation', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
