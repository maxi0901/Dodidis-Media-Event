<?php
declare(strict_types=1);

/**
 * Kalender-Abo Endpunkt
 * GET api/calendar.php?token=XXXXX
 *
 * Kein Login nötig – Authentifizierung über den persönlichen Token.
 * Gibt eine .ics-Datei zurück, die in Apple Kalender, Google Kalender
 * oder Outlook abonniert werden kann (webcal://-Link).
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$token = trim($_GET['token'] ?? '');
if (!$token || strlen($token) < 16) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Ungültiger oder fehlender Token.';
    exit;
}

$nameCol = users_name_column();

$user = db_one(
    "SELECT id, {$nameCol} AS name FROM users WHERE calendar_token = ?",
    [$token]
);

if (!$user) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kalender nicht gefunden. Der Link ist möglicherweise abgelaufen.';
    exit;
}

$uid       = $user['id'];
$userName  = $user['name'];
$roles     = array_column(db_all("SELECT role_name FROM user_roles WHERE user_id = ?", [$uid]), 'role_name');

$isAdmin    = in_array('admin',     $roles, true);
$isManager  = in_array('manager',  $roles, true);
$isVideograf= in_array('videograf',$roles, true);
$isCutter   = in_array('cutter',   $roles, true);
$isCutterOnly = $isCutter && !$isAdmin && !$isManager && !$isVideograf;

// ── Projekte holen ──────────────────────────────────────────────────────────
$pCols = "p.id, p.title, p.customer_id AS customerId, p.videograf_id AS videografId,
          p.cutter_id AS cutterId, COALESCE(p.shoot_date, sd.date) AS shootDate, p.deadline,
          p.posting_date AS postingDate, p.script, p.status";

if ($isAdmin || $isManager) {
    $projects = db_all(
        "SELECT $pCols FROM projects p
          LEFT JOIN shoot_days sd ON sd.id = p.shoot_day_id
          WHERE p.status != 'archiviert'
          ORDER BY COALESCE(p.shoot_date, sd.date)"
    );
} elseif ($isVideograf || $isCutter) {
    $projects = db_all(
        "SELECT $pCols FROM projects p
          LEFT JOIN shoot_days sd ON sd.id = p.shoot_day_id
          WHERE (p.videograf_id = ? OR p.cutter_id = ?) AND p.status != 'archiviert'
          ORDER BY COALESCE(p.shoot_date, sd.date)",
        [$uid, $uid]
    );
} else {
    $projects = [];
}

// ── Kunden-Namen (Map id → label) ──────────────────────────────────────────
$customerIds = array_unique(array_filter(array_column($projects, 'customerId')));
$customerMap = [];
if ($customerIds) {
    $ph   = implode(',', array_fill(0, count($customerIds), '?'));
    $rows = db_all("SELECT id, name, customer_number AS num FROM customers WHERE id IN ($ph)", $customerIds);
    foreach ($rows as $r) {
        $customerMap[$r['id']] = $r['name'] . ' (' . $r['num'] . ')';
    }
}

// ── Mitarbeiter-Namen (Map id → name) ──────────────────────────────────────
$allUsers = db_all("SELECT id, {$nameCol} AS name FROM users");
$userMap  = array_column($allUsers, 'name', 'id');

// ── Urlaube ────────────────────────────────────────────────────────────────
if ($isAdmin) {
    $vacations = db_all(
        "SELECT v.id, v.user_id AS userId, v.start_date AS startDate,
                v.end_date AS endDate, v.note
           FROM vacations v ORDER BY v.start_date"
    );
} else {
    $vacations = db_all(
        "SELECT v.id, v.user_id AS userId, v.start_date AS startDate,
                v.end_date AS endDate, v.note
           FROM vacations v WHERE v.user_id = ? ORDER BY v.start_date",
        [$uid]
    );
}

// ── Drehtage ──────────────────────────────────────────────────────────────
if ($isAdmin || $isManager) {
    $shootDays = db_all(
        "SELECT id, date, start_time AS startTime, end_time AS endTime,
                videograf_id AS videografId, customer_id AS customerId, note
           FROM shoot_days ORDER BY date"
    );
} elseif ($isVideograf) {
    $shootDays = db_all(
        "SELECT id, date, start_time AS startTime, end_time AS endTime,
                videograf_id AS videografId, customer_id AS customerId, note
           FROM shoot_days WHERE videograf_id = ? ORDER BY date",
        [$uid]
    );
} else {
    $shootDays = [];
}

// ── ICS-Hilfsfunktionen ────────────────────────────────────────────────────
function ics_escape(string $s): string {
    return str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $s);
}
function ics_allday(string $date): string {
    return date('Ymd', (int)strtotime($date));
}
function ics_allday_next(string $date): string {
    return date('Ymd', strtotime($date . ' +1 day'));
}
function ics_datetime_local(string $date, string $time = '18:00:00'): string {
    try {
        $tz = new DateTimeZone('Europe/Berlin');
        $dt = new DateTime($date . ' ' . $time, $tz);
        return $dt->format('Ymd\THis');
    } catch (Exception $e) {
        return '';
    }
}

$stamp   = gmdate('Ymd\THis\Z');
$calName = "Dodidis Media – {$userName}";

$lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Dodidis Media//Management Tool//DE',
    'CALSCALE:GREGORIAN',
    'METHOD:PUBLISH',
    'X-WR-CALNAME:' . ics_escape($calName),
    'X-WR-TIMEZONE:Europe/Berlin',
    'X-WR-CALDESC:Persönlicher Kalender – Dodidis Media',
    'REFRESH-INTERVAL;VALUE=DURATION:PT5M',
    'X-PUBLISHED-TTL:PT5M',
];

// ── Projekte als Events ────────────────────────────────────────────────────
foreach ($projects as $p) {
    $cLabel = $customerMap[$p['customerId']] ?? '–';
    $vgName = $userMap[$p['videografId']] ?? '–';
    $ctName = $userMap[$p['cutterId']]    ?? '–';
    $desc   = "Kunde: {$cLabel}\\nVideograf: {$vgName}\\nCutter: {$ctName}\\nStatus: {$p['status']}";
    if ($p['script']) {
        $desc .= '\\nSkript: ' . ics_escape($p['script']);
    }

    // Drehtag
    if ($p['shootDate']) {
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:project-' . $p['id'] . '-shoot@dodidis.media';
        $lines[] = 'DTSTAMP:' . $stamp;
        $lines[] = 'DTSTART;VALUE=DATE:' . ics_allday($p['shootDate']);
        $lines[] = 'DTEND;VALUE=DATE:'   . ics_allday_next($p['shootDate']);
        $lines[] = 'SUMMARY:🎥 Drehtag – ' . ics_escape($p['title']);
        $lines[] = 'DESCRIPTION:' . ics_escape($desc);
        $lines[] = 'CATEGORIES:Drehtag';
        $lines[] = 'END:VEVENT';
    }

    // Posting-Deadline (18 Uhr, Tag vor Posting) + Posting
    if ($p['postingDate']) {
        $deadlineDate = date('Y-m-d', strtotime($p['postingDate'] . ' -1 day'));
        $dlStart  = ics_datetime_local($deadlineDate, '18:00:00');
        $dlEnd    = ics_datetime_local($deadlineDate, '18:30:00');
        if ($dlStart) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:project-' . $p['id'] . '-cutter@dodidis.media';
            $lines[] = 'DTSTAMP:' . $stamp;
            $lines[] = 'DTSTART;TZID=Europe/Berlin:' . $dlStart;
            $lines[] = 'DTEND;TZID=Europe/Berlin:'   . $dlEnd;
            $lines[] = 'SUMMARY:⏰ Deadline – ' . ics_escape($p['title']);
            $lines[] = 'DESCRIPTION:' . ics_escape('Video muss fertig sein.\\n' . $desc);
            $lines[] = 'CATEGORIES:Deadline';
            $lines[] = 'END:VEVENT';
        }

        // Posting – nicht für reine Cutter
        if (!$isCutterOnly) {
            $pStart = ics_datetime_local($p['postingDate'], '18:00:00');
            $pEnd   = ics_datetime_local($p['postingDate'], '18:30:00');
            if ($pStart) {
                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:project-' . $p['id'] . '-posting@dodidis.media';
                $lines[] = 'DTSTAMP:' . $stamp;
                $lines[] = 'DTSTART;TZID=Europe/Berlin:' . $pStart;
                $lines[] = 'DTEND;TZID=Europe/Berlin:'   . $pEnd;
                $lines[] = 'SUMMARY:📤 Posting – ' . ics_escape($p['title']);
                $lines[] = 'DESCRIPTION:' . ics_escape($desc);
                $lines[] = 'CATEGORIES:Posting';
                $lines[] = 'END:VEVENT';
            }
        }
    }

    // Explizite Deadline (falls abweichend vom Posting)
    if ($p['deadline'] && $p['deadline'] !== $p['postingDate']) {
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:project-' . $p['id'] . '-deadline@dodidis.media';
        $lines[] = 'DTSTAMP:' . $stamp;
        $lines[] = 'DTSTART;VALUE=DATE:' . ics_allday($p['deadline']);
        $lines[] = 'DTEND;VALUE=DATE:'   . ics_allday_next($p['deadline']);
        $lines[] = 'SUMMARY:⏰ Deadline – ' . ics_escape($p['title']);
        $lines[] = 'DESCRIPTION:' . ics_escape($desc);
        $lines[] = 'CATEGORIES:Deadline';
        $lines[] = 'END:VEVENT';
    }
}

// ── Drehtage als Events ────────────────────────────────────────────────────
foreach ($shootDays as $sd) {
    $cLabel  = $customerMap[$sd['customerId']] ?? null;
    $vgName  = $userMap[$sd['videografId']] ?? '–';
    $summary = '📷 Drehtag' . ($cLabel ? ' – ' . $cLabel : '');
    $descParts = ["Videograf: {$vgName}"];
    if ($cLabel) $descParts[] = "Kunde: {$cLabel}";
    if ($sd['note']) $descParts[] = $sd['note'];
    $desc = implode('\\n', array_map('ics_escape', $descParts));

    if ($sd['startTime'] && $sd['endTime']) {
        $dtStart = ics_datetime_local($sd['date'], $sd['startTime']);
        $dtEnd   = ics_datetime_local($sd['date'], $sd['endTime']);
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:shootday-' . $sd['id'] . '@dodidis.media';
        $lines[] = 'DTSTAMP:' . $stamp;
        $lines[] = 'DTSTART;TZID=Europe/Berlin:' . $dtStart;
        $lines[] = 'DTEND;TZID=Europe/Berlin:'   . $dtEnd;
        $lines[] = 'SUMMARY:' . ics_escape($summary);
        $lines[] = 'DESCRIPTION:' . $desc;
        $lines[] = 'CATEGORIES:Drehtag';
        $lines[] = 'END:VEVENT';
    } else {
        $lines[] = 'BEGIN:VEVENT';
        $lines[] = 'UID:shootday-' . $sd['id'] . '@dodidis.media';
        $lines[] = 'DTSTAMP:' . $stamp;
        $lines[] = 'DTSTART;VALUE=DATE:' . ics_allday($sd['date']);
        $lines[] = 'DTEND;VALUE=DATE:'   . ics_allday_next($sd['date']);
        $lines[] = 'SUMMARY:' . ics_escape($summary);
        $lines[] = 'DESCRIPTION:' . $desc;
        $lines[] = 'CATEGORIES:Drehtag';
        $lines[] = 'END:VEVENT';
    }
}

// ── Urlaube als ganztägige Events ─────────────────────────────────────────
foreach ($vacations as $v) {
    $uName   = $userMap[$v['userId']] ?? '–';
    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:vacation-' . $v['id'] . '@dodidis.media';
    $lines[] = 'DTSTAMP:' . $stamp;
    $lines[] = 'DTSTART;VALUE=DATE:' . ics_allday($v['startDate']);
    $lines[] = 'DTEND;VALUE=DATE:'   . ics_allday_next($v['endDate']);
    $lines[] = 'SUMMARY:🏖️ Urlaub – ' . ics_escape($uName);
    if ($v['note']) $lines[] = 'DESCRIPTION:' . ics_escape($v['note']);
    $lines[] = 'CATEGORIES:Urlaub';
    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';

$ics = implode("\r\n", $lines) . "\r\n";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="dodidis-kalender.ics"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . strlen($ics));
echo $ics;
