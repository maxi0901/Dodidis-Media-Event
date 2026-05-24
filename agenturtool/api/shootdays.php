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

$cols = "id, date, start_time AS startTime, end_time AS endTime,
         videograf_id AS videografId, customer_id AS customerId,
         note, rescheduled_from AS rescheduledFrom, created_at AS createdAt";

switch ($method) {

    case 'GET': {
        if (!has_role('admin', 'manager', 'videograf')) {
            json_err(403, 'Keine Berechtigung.');
        }
        if ($id) {
            $sd = db_one("SELECT $cols FROM shoot_days WHERE id = ?", [$id]);
            if (!$sd) json_err(404, 'Drehtag nicht gefunden.');
            // Videograf darf nur eigene sehen
            if (!has_role('admin', 'manager') && $sd['videografId'] !== $session['uid']) {
                json_err(403, 'Keine Berechtigung.');
            }
            json_ok($sd);
        }
        if (has_role('admin', 'manager')) {
            $rows = db_all("SELECT $cols FROM shoot_days ORDER BY date DESC, start_time DESC");
        } else {
            $rows = db_all("SELECT $cols FROM shoot_days WHERE videograf_id = ? ORDER BY date DESC, start_time DESC",
                           [$session['uid']]);
        }
        json_ok($rows);
    }

    case 'POST': {
        require_role('admin', 'manager');
        $b = input_json();
        $date = as_date($b['date'] ?? null);
        if (!$date) json_err(400, 'date ist Pflicht.');
        $newId = $b['id'] ?? uid('sd');
        db_exec(
            "INSERT INTO shoot_days (id, date, start_time, end_time, videograf_id, customer_id, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $newId,
                $date,
                as_time($b['startTime'] ?? null),
                as_time($b['endTime']   ?? null),
                $b['videografId'] ?? null,
                $b['customerId']  ?? null,
                $b['note'] !== null ? (string)$b['note'] : null,
            ]
        );
        log_activity('shootDay', $newId, 'created');
        json_ok(db_one("SELECT $cols FROM shoot_days WHERE id = ?", [$newId]), 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        require_role('admin', 'manager');
        $cur = db_one("SELECT id, date, customer_id FROM shoot_days WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Drehtag nicht gefunden.');
        $b = input_json();
        $set = []; $vals = [];
        $newDate = null;
        if (array_key_exists('date', $b)) {
            $newDate = as_date($b['date']);
            $set[] = 'date = ?'; $vals[] = $newDate;
        }
        if (array_key_exists('startTime', $b))   { $set[] = 'start_time = ?';   $vals[] = as_time($b['startTime']); }
        if (array_key_exists('endTime', $b))     { $set[] = 'end_time = ?';     $vals[] = as_time($b['endTime']); }
        if (array_key_exists('videografId', $b)) { $set[] = 'videograf_id = ?'; $vals[] = $b['videografId'] ?: null; }
        if (array_key_exists('customerId', $b))  { $set[] = 'customer_id = ?';  $vals[] = $b['customerId']  ?: null; }
        if (array_key_exists('note', $b))        { $set[] = 'note = ?';         $vals[] = $b['note'] !== null ? (string)$b['note'] : null; }

        // Datum geändert → rescheduled_from setzen
        $rescheduled = $newDate && $newDate !== $cur['date'];
        if ($rescheduled) {
            $set[] = 'rescheduled_from = ?';
            $vals[] = $cur['date'];
        }

        if ($set) {
            $vals[] = $id;
            db_exec("UPDATE shoot_days SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        }

        // Kundenbenachrichtigung bei Verschiebung
        if ($rescheduled) {
            $customerId = $b['customerId'] ?? $cur['customer_id'] ?? null;
            if ($customerId) {
                $custUser = db_one("SELECT id FROM users WHERE customer_id = ? LIMIT 1", [$customerId]);
                if ($custUser) {
                    db_exec(
                        "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type)
                         VALUES (?, 'shoot_day_rescheduled', 'Drehtag verschoben', ?, ?, 'shoot_day')",
                        [$custUser['id'], 'Neues Datum: ' . $newDate, $id]
                    );
                }
            }
        }

        log_activity('shootDay', $id, $rescheduled ? 'rescheduled' : 'edited');
        json_ok(db_one("SELECT $cols FROM shoot_days WHERE id = ?", [$id]));
    }

    case 'DELETE': {
        require_role('admin', 'manager');
        if (!$id) json_err(400, 'id fehlt.');
        db_exec("DELETE FROM shoot_days WHERE id = ?", [$id]);
        log_activity('shootDay', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
