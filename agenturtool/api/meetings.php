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

$uid     = $session['uid'];
$isAdmin = has_role('admin');
$isMgr   = has_role('manager');
$canEdit = $isAdmin || $isMgr;

function meeting_to_doc(array $m): array {
    return [
        'id'          => $m['id'],
        'title'       => $m['title'],
        'date'        => $m['date'],
        'startTime'   => $m['start_time'],
        'endTime'     => $m['end_time'],
        'type'        => $m['type'],
        'link'        => $m['link'],
        'location'    => $m['location'] ?? null,
        'topics'      => json_decode($m['topics'] ?? 'null', true) ?? [],
        'attendeeIds' => json_decode($m['attendee_ids'] ?? 'null', true) ?? [],
        'customerId'  => $m['customer_id'],
        'createdBy'   => $m['created_by'],
        'createdAt'   => $m['created_at'],
        'updatedAt'   => $m['updated_at'],
    ];
}

switch ($method) {

    case 'GET': {
        if ($id) {
            $m = db_one("SELECT * FROM meetings WHERE id = ?", [$id]);
            if (!$m) json_err(404, 'Meeting nicht gefunden.');
            // Non-admin/manager: only attendees can read
            if (!$canEdit) {
                $ids = json_decode($m['attendee_ids'] ?? '[]', true) ?: [];
                if (!in_array($uid, $ids, true)) json_err(403, 'Keine Berechtigung.');
            }
            json_ok(meeting_to_doc($m));
        }
        if ($canEdit) {
            $rows = db_all("SELECT * FROM meetings ORDER BY date, start_time");
        } else {
            // Return only meetings where current user is an attendee
            $rows = db_all(
                "SELECT * FROM meetings WHERE JSON_CONTAINS(attendee_ids, ?, '$') ORDER BY date, start_time",
                [json_encode($uid)]
            );
        }
        json_ok(array_map('meeting_to_doc', $rows));
    }

    case 'POST': {
        if (!$canEdit) json_err(403, 'Nur Admins und Manager dürfen Meetings anlegen.');
        $b = input_json();
        $title = trim($b['title'] ?? '');
        $date  = as_date($b['date'] ?? null);
        if (!$title) json_err(400, 'Titel ist Pflicht.');
        if (!$date)  json_err(400, 'Datum ist Pflicht.');

        $newId       = $b['id'] ?? uid('mt');
        $startTime   = trim($b['startTime']  ?? '09:00:00') ?: '09:00:00';
        $endTime     = trim($b['endTime']    ?? '') ?: null;
        $type        = s($b['type']          ?? 'meeting', 64) ?: 'meeting';
        $link        = s($b['link']          ?? null, 500);
        $location    = s($b['location']      ?? null, 500);
        $topics      = isset($b['topics'])      && is_array($b['topics'])      ? json_encode($b['topics'],      JSON_UNESCAPED_UNICODE) : null;
        $attendeeIds = isset($b['attendeeIds']) && is_array($b['attendeeIds']) ? json_encode($b['attendeeIds'], JSON_UNESCAPED_UNICODE) : null;
        $customerId  = $b['customerId'] ?? null;

        db_exec(
            "INSERT INTO meetings (id, title, date, start_time, end_time, type, link, location, topics, attendee_ids, customer_id, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$newId, $title, $date, $startTime, $endTime, $type, $link, $location, $topics, $attendeeIds, $customerId, $uid]
        );
        log_activity('meeting', $newId, 'created', ['title' => $title]);
        json_ok(meeting_to_doc(db_one("SELECT * FROM meetings WHERE id = ?", [$newId])), 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        if (!$canEdit) json_err(403, 'Nur Admins und Manager dürfen Meetings bearbeiten.');
        $cur = db_one("SELECT id FROM meetings WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Meeting nicht gefunden.');

        $b   = input_json();
        $set = [];
        $vals = [];

        if (array_key_exists('title',       $b)) { $set[] = 'title = ?';        $vals[] = s($b['title'], 255); }
        if (array_key_exists('date',        $b)) { $set[] = 'date = ?';          $vals[] = as_date($b['date']); }
        if (array_key_exists('startTime',   $b)) { $set[] = 'start_time = ?';    $vals[] = $b['startTime'] ?: '09:00:00'; }
        if (array_key_exists('endTime',     $b)) { $set[] = 'end_time = ?';      $vals[] = $b['endTime'] ?: null; }
        if (array_key_exists('type',        $b)) { $set[] = 'type = ?';          $vals[] = s($b['type'], 64); }
        if (array_key_exists('link',        $b)) { $set[] = 'link = ?';          $vals[] = s($b['link'], 500); }
        if (array_key_exists('location',    $b)) { $set[] = 'location = ?';      $vals[] = s($b['location'], 500); }
        if (array_key_exists('topics',      $b)) { $set[] = 'topics = ?';        $vals[] = is_array($b['topics'])      ? json_encode($b['topics'],      JSON_UNESCAPED_UNICODE) : null; }
        if (array_key_exists('attendeeIds', $b)) { $set[] = 'attendee_ids = ?';  $vals[] = is_array($b['attendeeIds']) ? json_encode($b['attendeeIds'], JSON_UNESCAPED_UNICODE) : null; }
        if (array_key_exists('customerId',  $b)) { $set[] = 'customer_id = ?';   $vals[] = $b['customerId'] ?: null; }

        if ($set) {
            $vals[] = $id;
            db_exec("UPDATE meetings SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        }
        log_activity('meeting', $id, 'edited');
        json_ok(meeting_to_doc(db_one("SELECT * FROM meetings WHERE id = ?", [$id])));
    }

    case 'DELETE': {
        if (!$id) json_err(400, 'id fehlt.');
        if (!$canEdit) json_err(403, 'Nur Admins und Manager dürfen Meetings löschen.');
        $cur = db_one("SELECT id FROM meetings WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Meeting nicht gefunden.');
        db_exec("DELETE FROM meetings WHERE id = ?", [$id]);
        log_activity('meeting', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
