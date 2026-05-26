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

$cols         = "id, date, start_time AS startTime, end_time AS endTime,
                 videograf_id AS videografId, customer_id AS customerId,
                 note, rescheduled_from AS rescheduledFrom, created_at AS createdAt";
$colsFallback = "id, date, start_time AS startTime, end_time AS endTime,
                 videograf_id AS videografId, customer_id AS customerId,
                 note, created_at AS createdAt";

switch ($method) {

    case 'GET': {
        if (!has_role('admin', 'manager', 'videograf')) {
            json_err(403, 'Keine Berechtigung.');
        }
        if ($id) {
            try {
                $sd = db_one("SELECT $cols FROM shoot_days WHERE id = ?", [$id]);
            } catch (\Throwable $e) {
                $sd = db_one("SELECT $colsFallback FROM shoot_days WHERE id = ?", [$id]);
                if ($sd) $sd['rescheduledFrom'] = null;
            }
            if (!$sd) json_err(404, 'Drehtag nicht gefunden.');
            if (!has_role('admin', 'manager') && $sd['videografId'] !== $session['uid']) {
                json_err(403, 'Keine Berechtigung.');
            }
            json_ok($sd);
        }
        if (has_role('admin', 'manager')) {
            try {
                $rows = db_all("SELECT $cols FROM shoot_days ORDER BY date DESC, start_time DESC");
            } catch (\Throwable $e) {
                $rows = db_all("SELECT $colsFallback FROM shoot_days ORDER BY date DESC, start_time DESC");
                foreach ($rows as &$r) $r['rescheduledFrom'] = null;
                unset($r);
            }
        } else {
            try {
                $rows = db_all("SELECT $cols FROM shoot_days WHERE videograf_id = ? ORDER BY date DESC, start_time DESC",
                               [$session['uid']]);
            } catch (\Throwable $e) {
                $rows = db_all("SELECT $colsFallback FROM shoot_days WHERE videograf_id = ? ORDER BY date DESC, start_time DESC",
                               [$session['uid']]);
                foreach ($rows as &$r) $r['rescheduledFrom'] = null;
                unset($r);
            }
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
        try {
            $row = db_one("SELECT $cols FROM shoot_days WHERE id = ?", [$newId]);
        } catch (\Throwable $e) {
            $row = db_one("SELECT $colsFallback FROM shoot_days WHERE id = ?", [$newId]);
            if ($row) $row['rescheduledFrom'] = null;
        }
        json_ok($row, 201);
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

        $rescheduled = $newDate && $newDate !== $cur['date'];
        if ($rescheduled) {
            // rescheduled_from in eigenem Statement – Spalte existiert ggf. noch nicht
            $set[] = 'rescheduled_from = ?';
            $vals[] = $cur['date'];
        }

        if ($set) {
            $setWithoutRescheduled = array_filter($set, fn($s) => !str_starts_with($s, 'rescheduled_from'));
            $valsWithoutRescheduled = array_values(array_slice($vals, 0, count($setWithoutRescheduled)));
            try {
                $allVals   = $vals;
                $allVals[] = $id;
                db_exec("UPDATE shoot_days SET " . implode(', ', $set) . " WHERE id = ?", $allVals);
            } catch (\Throwable $e) {
                // rescheduled_from-Spalte fehlt – ohne diese Spalte nochmal versuchen
                if ($setWithoutRescheduled) {
                    $valsWithoutRescheduled[] = $id;
                    db_exec("UPDATE shoot_days SET " . implode(', ', array_values($setWithoutRescheduled)) . " WHERE id = ?", $valsWithoutRescheduled);
                }
            }
        }

        log_activity('shootDay', $id, $rescheduled ? 'rescheduled' : 'edited');
        try {
            $result = db_one("SELECT $cols FROM shoot_days WHERE id = ?", [$id]);
        } catch (\Throwable $e) {
            $result = db_one("SELECT $colsFallback FROM shoot_days WHERE id = ?", [$id]);
            if ($result) $result['rescheduledFrom'] = null;
        }
        json_ok($result);
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
