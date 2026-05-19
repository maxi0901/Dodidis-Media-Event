<?php
declare(strict_types=1);

/**
 * Einmaliger Legacy-Importer.
 *
 * Quellen (in dieser Reihenfolge, das erste mit Daten gewinnt):
 *   1. agentur-backup-2026-05-19.json  (vorhandenes JSON-Backup)
 *   2. Alte JSON-Storage-Tabellen mit Spalte `data` (falls bereits in MySQL)
 *
 * Nutzung:
 *   CLI:    php migrate_legacy.php
 *   Browser:/agenturtool/migrate_legacy.php?token=<config.migration_token>
 *
 * Idempotent: INSERT … ON DUPLICATE KEY UPDATE überall.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/response.php';
require_once __DIR__ . '/includes/helpers.php';

$cfg     = require __DIR__ . '/config.php';
$isCli   = (php_sapi_name() === 'cli');
$token   = $_GET['token'] ?? '';
$echo    = function (string $msg) use ($isCli) {
    if ($isCli) echo $msg . "\n";
    else echo htmlspecialchars($msg) . "<br>\n";
};

if (!$isCli) {
    if (empty($cfg['migration_token']) || !hash_equals($cfg['migration_token'], $token)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo "<pre style='font-family:monospace'>";
}

$pdo = db();
$errors = [];

// --- Quelle laden ---
$backupFile = __DIR__ . '/agentur-backup-2026-05-19.json';
$source     = null;

if (is_file($backupFile)) {
    $json = file_get_contents($backupFile);
    $source = json_decode($json, true);
    $echo('[source] JSON-Backup geladen: ' . $backupFile);
}

// Fallback: alte data-JSON-Tabellen lesen, falls Backup-Datei fehlt
if (!$source) {
    $source = ['users'=>[], 'customers'=>[], 'projects'=>[], 'vacations'=>[], 'todos'=>[], 'shootDays'=>[]];
    foreach ([
        'users'      => 'users',
        'customers'  => 'customers',
        'projects'   => 'projects',
        'vacations'  => 'vacations',
        'todos'      => 'todos',
        'shoot_days' => 'shootDays',
    ] as $table => $key) {
        try {
            $stmt = $pdo->query("SELECT data FROM `$table`");
            foreach ($stmt as $r) {
                $obj = json_decode($r['data'], true);
                if ($obj) $source[$key][] = $obj;
            }
            $echo("[source] $table aus alter data-Spalte geladen (" . count($source[$key]) . ' Datensätze)');
        } catch (Throwable $e) {
            // Alte Tabellen existieren u.U. nicht mehr (install.sql droppt sie) – nicht kritisch
        }
    }
}

$pdo->beginTransaction();

try {
    // ---------- USERS ----------
    foreach (($source['users'] ?? []) as $u) {
        if (!isset($u['id'], $u['username'])) continue;
        $pdo->prepare(
            "INSERT INTO users (id, username, name, email, password_hash, avatar_color, avatar_image, calendar_prefs)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                username = VALUES(username),
                name = VALUES(name),
                email = VALUES(email),
                password_hash = VALUES(password_hash),
                avatar_color = VALUES(avatar_color),
                avatar_image = VALUES(avatar_image),
                calendar_prefs = VALUES(calendar_prefs)"
        )->execute([
            $u['id'],
            $u['username'],
            $u['name'] ?? $u['username'],
            !empty($u['email']) ? $u['email'] : null,
            $u['password'] ?? $u['password_hash'] ?? '',
            $u['avatarColor'] ?? null,
            $u['avatarImage'] ?? null,
            isset($u['calendarPrefs']) ? json_encode($u['calendarPrefs'], JSON_UNESCAPED_UNICODE) : null,
        ]);

        $roles = $u['roles'] ?? ($u['role'] ? [$u['role']] : []);
        $stmtRole = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
        foreach (array_unique($roles) as $r) {
            if (in_array($r, ['admin','manager','videograf','cutter','mitarbeiter'], true)) {
                $stmtRole->execute([$u['id'], $r]);
            }
        }
    }
    $echo('[ok] users: ' . count($source['users'] ?? []));

    // ---------- CUSTOMERS ----------
    $stmtC = $pdo->prepare(
        "INSERT INTO customers (
            id, name, customer_number, manager_id, pin_hash,
            email, phone, contact_name, social_instagram, social_tiktok, notes,
            address_street, address_zip, address_city,
            billing_same_as_address, billing_street, billing_zip, billing_city,
            vat_id, billing_email, contract_start, package, monthly_rate, videos_per_month, status,
            contract_signed, deposit_received, kickoff_done, social_access, first_shoot
         ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
         ) ON DUPLICATE KEY UPDATE
            name = VALUES(name), manager_id = VALUES(manager_id), pin_hash = VALUES(pin_hash),
            email = VALUES(email), phone = VALUES(phone), contact_name = VALUES(contact_name),
            social_instagram = VALUES(social_instagram), social_tiktok = VALUES(social_tiktok),
            notes = VALUES(notes),
            address_street = VALUES(address_street), address_zip = VALUES(address_zip), address_city = VALUES(address_city),
            billing_same_as_address = VALUES(billing_same_as_address),
            billing_street = VALUES(billing_street), billing_zip = VALUES(billing_zip), billing_city = VALUES(billing_city),
            vat_id = VALUES(vat_id), billing_email = VALUES(billing_email),
            contract_start = VALUES(contract_start), package = VALUES(package),
            monthly_rate = VALUES(monthly_rate), videos_per_month = VALUES(videos_per_month),
            status = VALUES(status),
            contract_signed = VALUES(contract_signed), deposit_received = VALUES(deposit_received),
            kickoff_done = VALUES(kickoff_done), social_access = VALUES(social_access), first_shoot = VALUES(first_shoot)"
    );

    foreach (($source['customers'] ?? []) as $c) {
        if (!isset($c['id'], $c['customerNumber'])) continue;
        $cl = $c['checklist'] ?? [];
        $stmtC->execute([
            $c['id'],
            $c['name'] ?? '',
            $c['customerNumber'],
            !empty($c['managerId']) ? $c['managerId'] : null,
            !empty($c['pinHash'])   ? $c['pinHash']   : null,
            !empty($c['email']) ? $c['email'] : null,
            !empty($c['phone']) ? $c['phone'] : null,
            !empty($c['contactName']) ? $c['contactName'] : null,
            !empty($c['socialInstagram']) ? $c['socialInstagram'] : null,
            !empty($c['socialTiktok'])    ? $c['socialTiktok']    : null,
            !empty($c['notes']) ? $c['notes'] : null,
            !empty($c['addressStreet']) ? $c['addressStreet'] : null,
            !empty($c['addressZip'])    ? $c['addressZip']    : null,
            !empty($c['addressCity'])   ? $c['addressCity']   : null,
            !empty($c['billingSameAsAddress']) ? 1 : 0,
            !empty($c['billingStreet']) ? $c['billingStreet'] : null,
            !empty($c['billingZip'])    ? $c['billingZip']    : null,
            !empty($c['billingCity'])   ? $c['billingCity']   : null,
            !empty($c['vatId'])         ? $c['vatId']         : null,
            !empty($c['billingEmail'])  ? $c['billingEmail']  : null,
            as_date($c['contractStart'] ?? null),
            !empty($c['package']) ? $c['package'] : null,
            isset($c['monthlyRate'])     && $c['monthlyRate'] !== ''     ? (string)$c['monthlyRate']     : null,
            isset($c['videosPerMonth'])  && $c['videosPerMonth'] !== ''  ? (int)$c['videosPerMonth']     : null,
            in_array($c['status'] ?? null, ['onboarding','active','paused'], true) ? $c['status'] : null,
            !empty($cl['contractSigned'])  ? 1 : 0,
            !empty($cl['depositReceived']) ? 1 : 0,
            !empty($cl['kickoffDone'])     ? 1 : 0,
            !empty($cl['socialAccess'])    ? 1 : 0,
            !empty($cl['firstShoot'])      ? 1 : 0,
        ]);
    }
    $echo('[ok] customers: ' . count($source['customers'] ?? []));

    // ---------- SHOOT_DAYS ----------
    $stmtSD = $pdo->prepare(
        "INSERT INTO shoot_days (id, date, start_time, end_time, videograf_id, customer_id, note)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           date = VALUES(date), start_time = VALUES(start_time), end_time = VALUES(end_time),
           videograf_id = VALUES(videograf_id), customer_id = VALUES(customer_id), note = VALUES(note)"
    );
    foreach (($source['shootDays'] ?? []) as $sd) {
        if (!isset($sd['id'], $sd['date'])) continue;
        $stmtSD->execute([
            $sd['id'],
            as_date($sd['date']),
            as_time($sd['startTime'] ?? null),
            as_time($sd['endTime']   ?? null),
            !empty($sd['videografId']) ? $sd['videografId'] : null,
            !empty($sd['customerId'])  ? $sd['customerId']  : null,
            !empty($sd['note']) ? $sd['note'] : null,
        ]);
    }
    $echo('[ok] shoot_days: ' . count($source['shootDays'] ?? []));

    // ---------- PROJECTS ----------
    $stmtP = $pdo->prepare(
        "INSERT INTO projects (
            id, title, customer_id, videograf_id, cutter_id,
            shoot_date, shoot_day_id, deadline, posting_date, script,
            status, is_internal, approved_at, created_at
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            title = VALUES(title), customer_id = VALUES(customer_id),
            videograf_id = VALUES(videograf_id), cutter_id = VALUES(cutter_id),
            shoot_date = VALUES(shoot_date), shoot_day_id = VALUES(shoot_day_id),
            deadline = VALUES(deadline), posting_date = VALUES(posting_date),
            script = VALUES(script), status = VALUES(status), is_internal = VALUES(is_internal),
            approved_at = VALUES(approved_at)"
    );
    $validStatus = ['skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert'];
    foreach (($source['projects'] ?? []) as $p) {
        if (!isset($p['id'], $p['title'])) continue;
        $status = in_array($p['status'] ?? null, $validStatus, true) ? $p['status'] : 'skript';
        $stmtP->execute([
            $p['id'],
            $p['title'],
            !empty($p['customerId'])  ? $p['customerId']  : null,
            !empty($p['videografId']) ? $p['videografId'] : null,
            !empty($p['cutterId'])    ? $p['cutterId']    : null,
            as_date($p['shootDate']   ?? null),
            !empty($p['shootDayId'])  ? $p['shootDayId']  : null,
            as_date($p['deadline']    ?? null),
            as_date($p['postingDate'] ?? null),
            $p['script'] ?? null,
            $status,
            !empty($p['isInternal']) ? 1 : 0,
            !empty($p['approvedAt']) ? date('Y-m-d H:i:s', strtotime($p['approvedAt'])) : null,
            !empty($p['createdAt'])  ? date('Y-m-d H:i:s', strtotime($p['createdAt']))  : date('Y-m-d H:i:s'),
        ]);
    }
    $echo('[ok] projects: ' . count($source['projects'] ?? []));

    // ---------- VACATIONS ----------
    $stmtV = $pdo->prepare(
        "INSERT INTO vacations (id, user_id, start_date, end_date, note)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           user_id = VALUES(user_id), start_date = VALUES(start_date),
           end_date = VALUES(end_date), note = VALUES(note)"
    );
    foreach (($source['vacations'] ?? []) as $v) {
        if (!isset($v['id'], $v['userId'], $v['startDate'], $v['endDate'])) continue;
        $stmtV->execute([
            $v['id'],
            $v['userId'],
            as_date($v['startDate']),
            as_date($v['endDate']),
            !empty($v['note']) ? $v['note'] : null,
        ]);
    }
    $echo('[ok] vacations: ' . count($source['vacations'] ?? []));

    // ---------- TODOS (+ assignees + seen_by) ----------
    $stmtT  = $pdo->prepare(
        "INSERT INTO todos (id, title, description, created_by, due_date, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           title = VALUES(title), description = VALUES(description),
           created_by = VALUES(created_by), due_date = VALUES(due_date), status = VALUES(status)"
    );
    $stmtTA = $pdo->prepare("INSERT IGNORE INTO todo_assignees (todo_id, user_id) VALUES (?, ?)");
    $stmtTS = $pdo->prepare("INSERT IGNORE INTO todo_seen_by   (todo_id, user_id) VALUES (?, ?)");
    foreach (($source['todos'] ?? []) as $t) {
        if (!isset($t['id'], $t['title'])) continue;
        $stmtT->execute([
            $t['id'],
            $t['title'],
            $t['description'] ?? null,
            !empty($t['createdById']) ? $t['createdById'] : null,
            as_date($t['dueDate'] ?? null),
            in_array($t['status'] ?? null, ['open','done'], true) ? $t['status'] : 'open',
            !empty($t['createdAt']) ? date('Y-m-d H:i:s', strtotime($t['createdAt'])) : date('Y-m-d H:i:s'),
        ]);
        foreach ((array)($t['assigneeIds'] ?? []) as $u) {
            if ($u) $stmtTA->execute([$t['id'], $u]);
        }
        foreach ((array)($t['seenBy'] ?? []) as $u) {
            if ($u) $stmtTS->execute([$t['id'], $u]);
        }
    }
    $echo('[ok] todos: ' . count($source['todos'] ?? []));

    // ---------- APP_CONFIG ----------
    if (!empty($source['calendarConfig'])) {
        $pdo->prepare(
            "INSERT INTO app_config (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
        )->execute(['calendarConfig', json_encode($source['calendarConfig'], JSON_UNESCAPED_UNICODE)]);
        $echo('[ok] app_config: calendarConfig');
    }

    $pdo->commit();
    $echo('[done] Migration erfolgreich.');
} catch (Throwable $e) {
    $pdo->rollBack();
    $echo('[FAIL] ' . $e->getMessage());
    $errors[] = $e->getMessage();
    http_response_code(500);
}

if (!$isCli) echo "</pre>";
