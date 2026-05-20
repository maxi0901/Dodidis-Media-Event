<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_err(405, 'GET erwartet.');
}

$pdo     = db();
$initial = isset($_GET['initial']) && $_GET['initial'] !== '0' && $_GET['initial'] !== '';

// "last_change": Maximum aller relevanten updated_at-Zeitstempel
$lastChange = (int)$pdo->query("
    SELECT GREATEST(
        COALESCE((SELECT UNIX_TIMESTAMP(MAX(updated_at)) FROM users),     0),
        COALESCE((SELECT UNIX_TIMESTAMP(MAX(updated_at)) FROM customers), 0),
        COALESCE((SELECT UNIX_TIMESTAMP(MAX(updated_at)) FROM projects),  0),
        COALESCE((SELECT UNIX_TIMESTAMP(MAX(updated_at)) FROM todos),     0),
        COALESCE((SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM vacations), 0),
        COALESCE((SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM shoot_days),0),
        COALESCE((SELECT UNIX_TIMESTAMP(MAX(ts))         FROM activity_log),0)
    ) AS t
")->fetchColumn();

$etag = 'W/"' . $lastChange . ($initial ? '-i' : '') . '"';
if (!$initial && ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    header('ETag: ' . $etag);
    exit;
}
header('ETag: ' . $etag);

$out = [
    'last_change' => $lastChange,
    'counts' => [
        'projects_open'     => (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status NOT IN ('freigegeben','archiviert')")->fetchColumn(),
        'todos_open'        => (int)$pdo->query("SELECT COUNT(*) FROM todos WHERE status='open'")->fetchColumn(),
        'shoot_days_next_7' => (int)$pdo->query("SELECT COUNT(*) FROM shoot_days WHERE date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
        'vacations_active'  => (int)$pdo->query("SELECT COUNT(*) FROM vacations WHERE CURDATE() BETWEEN start_date AND end_date")->fetchColumn(),
    ],
    'recent_activity' => db_all("SELECT ts, scope, ref_id AS refId, action, actor_id AS actorId
                                   FROM activity_log ORDER BY id DESC LIMIT 20"),
];

if ($initial) {
    // Vollständige Listen für den App-Start
    $isStaff = ($session['type'] ?? '') === 'staff';

    // Users: roles eager-loaded
    $nameCol = users_name_column();
    $userRows = db_all(
        "SELECT id, username, {$nameCol} AS name" . (has_role('admin','manager') ? ", email" : "") .
        ", avatar_color AS avatarColor, avatar_image AS avatarImage, calendar_prefs FROM users ORDER BY {$nameCol}"
    );
    $roleAll = db_all("SELECT user_id, role_name FROM user_roles");
    $byUser  = [];
    foreach ($roleAll as $r) { $byUser[$r['user_id']][] = $r['role_name']; }
    foreach ($userRows as &$u) {
        $u['roles'] = $byUser[$u['id']] ?? [];
        if ($u['calendar_prefs']) {
            $u['calendarPrefs'] = json_decode($u['calendar_prefs'], true);
        }
        unset($u['calendar_prefs']);
    }
    unset($u);

    // Customers: für staff alle, für customer nur sich selbst
    $custSelect = "id, name, customer_number AS customerNumber, manager_id AS managerId,
                   email, phone, contact_name AS contactName,
                   social_instagram AS socialInstagram, social_tiktok AS socialTiktok,
                   notes, address_street AS addressStreet, address_zip AS addressZip, address_city AS addressCity,
                   billing_same_as_address AS billingSameAsAddress,
                   billing_street AS billingStreet, billing_zip AS billingZip, billing_city AS billingCity,
                   vat_id AS vatId, billing_email AS billingEmail,
                   contract_start AS contractStart, package, monthly_rate AS monthlyRate,
                   videos_per_month AS videosPerMonth, status,
                   contract_signed, deposit_received, kickoff_done, social_access, first_shoot,
                   created_at AS createdAt";
    if ($isStaff) {
        $customers = db_all("SELECT $custSelect FROM customers ORDER BY customer_number");
    } else {
        $customers = db_all("SELECT $custSelect FROM customers WHERE id = ?", [$session['cid']]);
    }
    foreach ($customers as &$c) {
        $c['checklist'] = [
            'contractSigned'  => (bool)$c['contract_signed'],
            'depositReceived' => (bool)$c['deposit_received'],
            'kickoffDone'     => (bool)$c['kickoff_done'],
            'socialAccess'    => (bool)$c['social_access'],
            'firstShoot'      => (bool)$c['first_shoot'],
        ];
        unset($c['contract_signed'], $c['deposit_received'], $c['kickoff_done'], $c['social_access'], $c['first_shoot']);
    }
    unset($c);

    $projSelect = "id, title, customer_id AS customerId, videograf_id AS videografId,
                   cutter_id AS cutterId, shoot_date AS shootDate, shoot_day_id AS shootDayId,
                   deadline, posting_date AS postingDate, script, status,
                   is_internal AS isInternal, approved_at AS approvedAt,
                   created_at AS createdAt";

    if ($isStaff) {
        if (has_role('admin', 'manager')) {
            $projects = db_all("SELECT $projSelect FROM projects ORDER BY COALESCE(deadline, posting_date, shoot_date, created_at) DESC");
        } else {
            $projects = db_all(
                "SELECT $projSelect FROM projects WHERE videograf_id = ? OR cutter_id = ?
                 ORDER BY COALESCE(deadline, posting_date, shoot_date, created_at) DESC",
                [$session['uid'], $session['uid']]
            );
        }
    } else {
        $projects = db_all("SELECT $projSelect FROM projects WHERE customer_id = ?", [$session['cid']]);
    }

    // Todos
    if ($isStaff) {
        if (has_role('admin', 'manager')) {
            $todos = db_all("SELECT id, title, description, created_by AS createdById, due_date AS dueDate, status,
                                    created_at AS createdAt, updated_at AS updatedAt
                               FROM todos ORDER BY status='done', COALESCE(due_date, created_at)");
        } else {
            $todos = db_all(
                "SELECT id, title, description, created_by AS createdById, due_date AS dueDate, status,
                        created_at AS createdAt, updated_at AS updatedAt
                   FROM todos
                  WHERE id IN (SELECT todo_id FROM todo_assignees WHERE user_id = ?)
                     OR created_by = ?
                  ORDER BY status='done', COALESCE(due_date, created_at)",
                [$session['uid'], $session['uid']]
            );
        }
        if ($todos) {
            $ids   = array_column($todos, 'id');
            $place = implode(',', array_fill(0, count($ids), '?'));
            $a = db_all("SELECT todo_id, user_id FROM todo_assignees WHERE todo_id IN ($place)", $ids);
            $s = db_all("SELECT todo_id, user_id FROM todo_seen_by   WHERE todo_id IN ($place)", $ids);
            $byA = []; $byS = [];
            foreach ($a as $r) $byA[$r['todo_id']][] = $r['user_id'];
            foreach ($s as $r) $byS[$r['todo_id']][] = $r['user_id'];
            foreach ($todos as &$t) {
                $t['assigneeIds'] = $byA[$t['id']] ?? [];
                $t['seenBy']      = $byS[$t['id']] ?? [];
            }
            unset($t);
        }
    } else {
        $todos = [];
    }

    // Vacations
    $vacations = $isStaff
        ? db_all("SELECT id, user_id AS userId, start_date AS startDate, end_date AS endDate, note,
                         approved_by AS approvedBy, approved_at AS approvedAt
                    FROM vacations ORDER BY start_date DESC")
        : [];

    // ShootDays
    if ($isStaff) {
        if (has_role('admin', 'manager')) {
            $shootDays = db_all("SELECT id, date, start_time AS startTime, end_time AS endTime,
                                        videograf_id AS videografId, customer_id AS customerId,
                                        note, created_at AS createdAt
                                   FROM shoot_days ORDER BY date DESC");
        } elseif (has_role('videograf')) {
            $shootDays = db_all("SELECT id, date, start_time AS startTime, end_time AS endTime,
                                        videograf_id AS videografId, customer_id AS customerId,
                                        note, created_at AS createdAt
                                   FROM shoot_days WHERE videograf_id = ? ORDER BY date DESC",
                                [$session['uid']]);
        } else {
            $shootDays = [];
        }
    } else {
        $shootDays = [];
    }

    $out['users']     = $userRows;
    $out['customers'] = $customers;
    $out['projects']  = $projects;
    $out['todos']     = $todos;
    $out['vacations'] = $vacations;
    $out['shootDays'] = $shootDays;
}

json_ok($out);
