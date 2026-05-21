<?php
declare(strict_types=1);

/**
 * Schema-Migrations-Runner
 *
 * Einmalig im Browser aufrufen (als Admin eingeloggt):
 *   https://<domain>/agenturtool/run_migrations.php
 *
 * Idempotent – kann beliebig oft ausgeführt werden.
 * Erfordert aktive Admin-Session (via index.php / login.php einloggen).
 */

require_once __DIR__ . '/includes/response.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth-check.php';

// --- Auth-Check (Admin-Session erforderlich) ---
start_app_session();
if (empty($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}
if (!in_array('admin', $_SESSION['roles'] ?? [], true)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body><h2>403 Forbidden</h2><p>Nur Admins können Migrationen ausführen.</p></body></html>';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Schema-Migration – Dodidis Media</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', monospace; background: #0a0b0f; color: #f5f5f7; margin: 0; padding: 32px; }
  h1 { color: #4aa198; font-size: 22px; margin-bottom: 8px; }
  .sub { color: #a1a1aa; font-size: 13px; margin-bottom: 28px; }
  .section { margin-bottom: 20px; }
  .section-title { color: #4aa198; font-weight: 600; font-size: 15px; margin-bottom: 8px; }
  .ok  { color: #30d158; }
  .skip { color: #a1a1aa; }
  .err { color: #ff453a; }
  .warn { color: #ff9f0a; }
  pre { background: #151820; border: 1px solid rgba(255,255,255,.08); border-radius: 10px; padding: 16px; font-size: 13px; line-height: 1.7; white-space: pre-wrap; word-break: break-word; }
  .done { background: rgba(48,209,88,.1); border: 1px solid rgba(48,209,88,.25); border-radius: 10px; padding: 16px 20px; margin-top: 24px; color: #30d158; font-weight: 600; }
  .link { color: #4aa198; text-decoration: none; }
  .link:hover { text-decoration: underline; }
</style>
</head>
<body>
<h1>Schema-Migration</h1>
<div class="sub">Führt alle ausstehenden Datenbank-Änderungen aus. Sicher mehrfach ausführbar.</div>
<pre><?php

$db   = '';
$pdo  = null;

function ok(string $msg): void  { echo '<span class="ok">✓</span>  ' . htmlspecialchars($msg) . "\n"; }
function skip(string $msg): void{ echo '<span class="skip">–</span>  ' . htmlspecialchars($msg) . "\n"; }
function err(string $msg): void { echo '<span class="err">✗</span>  ' . htmlspecialchars($msg) . "\n"; }
function sect(string $title): void { echo "\n<span class=\"section-title\">── " . htmlspecialchars($title) . " ──</span>\n"; }

function col_exists(PDO $pdo, string $db, string $table, string $col): bool {
    $r = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns
                         WHERE table_schema = ? AND table_name = ? AND column_name = ?");
    $r->execute([$db, $table, $col]);
    return (int)$r->fetchColumn() > 0;
}

function tbl_exists(PDO $pdo, string $db, string $table): bool {
    $r = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables
                         WHERE table_schema = ? AND table_name = ?");
    $r->execute([$db, $table]);
    return (int)$r->fetchColumn() > 0;
}

function exec_sql(PDO $pdo, string $sql, string $label): void {
    try {
        $pdo->exec($sql);
        ok($label);
    } catch (Throwable $e) {
        err($label . ': ' . $e->getMessage());
    }
}

// --- Verbindungstest ---
sect('Datenbankverbindung');
try {
    $pdo = db();
    $db  = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
    ok('Verbunden mit: ' . $db);
} catch (Throwable $e) {
    err('Verbindung fehlgeschlagen: ' . $e->getMessage());
    echo '</pre><div class="done" style="background:rgba(255,69,58,.1);border-color:rgba(255,69,58,.3);color:#ff453a;">Migration abgebrochen – Datenbank nicht erreichbar.</div></body></html>';
    exit;
}

// ============================================================
// BLOCK A – Fehlende Tabellen anlegen
// ============================================================
sect('Block A – Fehlende Tabellen');

// activity_log
if (!tbl_exists($pdo, $db, 'activity_log')) {
    exec_sql($pdo, "
        CREATE TABLE activity_log (
          id        BIGINT      NOT NULL AUTO_INCREMENT,
          ts        DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
          scope     VARCHAR(32) NOT NULL,
          ref_id    VARCHAR(64) NULL,
          action    VARCHAR(64) NOT NULL,
          actor_id  VARCHAR(64) NULL,
          details   JSON        NULL,
          created_at DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_al_scope_ref (scope, ref_id),
          KEY idx_al_ts (ts)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", 'activity_log erstellt');
} else {
    skip('activity_log existiert bereits');
    // Fehlende Spalten nachrüsten (ältere Schema-Versionen hatten nicht alle Spalten)
    if (!col_exists($pdo, $db, 'activity_log', 'scope')) {
        exec_sql($pdo, "ALTER TABLE activity_log ADD COLUMN scope VARCHAR(32) NOT NULL DEFAULT 'system' AFTER ts", 'activity_log.scope hinzugefügt');
    } else { skip('activity_log.scope existiert bereits'); }
    if (!col_exists($pdo, $db, 'activity_log', 'ref_id')) {
        exec_sql($pdo, "ALTER TABLE activity_log ADD COLUMN ref_id VARCHAR(64) NULL DEFAULT NULL AFTER scope", 'activity_log.ref_id hinzugefügt');
    } else { skip('activity_log.ref_id existiert bereits'); }
    if (!col_exists($pdo, $db, 'activity_log', 'actor_id')) {
        exec_sql($pdo, "ALTER TABLE activity_log ADD COLUMN actor_id VARCHAR(64) NULL DEFAULT NULL AFTER action", 'activity_log.actor_id hinzugefügt');
    } else { skip('activity_log.actor_id existiert bereits'); }
    if (!col_exists($pdo, $db, 'activity_log', 'details')) {
        exec_sql($pdo, "ALTER TABLE activity_log ADD COLUMN details JSON NULL DEFAULT NULL", 'activity_log.details hinzugefügt');
    } else { skip('activity_log.details existiert bereits'); }
}

// project_files
if (!tbl_exists($pdo, $db, 'project_files')) {
    exec_sql($pdo, "
        CREATE TABLE project_files (
          id          BIGINT       NOT NULL AUTO_INCREMENT,
          project_id  VARCHAR(64)  NOT NULL,
          kind        ENUM('script','contract','correction','other','avatar') NOT NULL DEFAULT 'other',
          filename    VARCHAR(255) NOT NULL,
          mime        VARCHAR(96)  NOT NULL,
          size        INT UNSIGNED NOT NULL,
          path        VARCHAR(255) NOT NULL,
          uploaded_by VARCHAR(64)  NULL,
          uploaded_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_pf_project (project_id, uploaded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", 'project_files erstellt');
} else {
    skip('project_files existiert bereits');
}

// app_config
if (!tbl_exists($pdo, $db, 'app_config')) {
    exec_sql($pdo, "
        CREATE TABLE app_config (
          `key`      VARCHAR(64) NOT NULL,
          `value`    JSON        NOT NULL,
          updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", 'app_config erstellt');
} else {
    if (!col_exists($pdo, $db, 'app_config', 'key')) {
        // Tabelle existiert mit falschem Schema (key-Spalte fehlt) → drop + neu anlegen
        exec_sql($pdo, "DROP TABLE app_config", 'app_config (altes Schema) gelöscht');
        exec_sql($pdo, "
            CREATE TABLE app_config (
              `key`      VARCHAR(64) NOT NULL,
              `value`    JSON        NOT NULL,
              updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ", 'app_config (korrektes Schema) neu erstellt');
    } else {
        skip('app_config existiert bereits');
    }
}

// customer_checklists
if (!tbl_exists($pdo, $db, 'customer_checklists')) {
    exec_sql($pdo, "
        CREATE TABLE customer_checklists (
          customer_id      VARCHAR(64) NOT NULL,
          contract_signed  TINYINT(1)  NOT NULL DEFAULT 0,
          deposit_received TINYINT(1)  NOT NULL DEFAULT 0,
          kickoff_done     TINYINT(1)  NOT NULL DEFAULT 0,
          social_access    TINYINT(1)  NOT NULL DEFAULT 0,
          first_shoot      TINYINT(1)  NOT NULL DEFAULT 0,
          PRIMARY KEY (customer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", 'customer_checklists erstellt');

    // Bestehende Checklist-Daten aus customers migrieren (falls Spalten noch existieren)
    if (col_exists($pdo, $db, 'customers', 'contract_signed')) {
        exec_sql($pdo, "
            INSERT IGNORE INTO customer_checklists
                (customer_id, contract_signed, deposit_received, kickoff_done, social_access, first_shoot)
            SELECT id,
                   COALESCE(contract_signed, 0),
                   COALESCE(deposit_received, 0),
                   COALESCE(kickoff_done, 0),
                   COALESCE(social_access, 0),
                   COALESCE(first_shoot, 0)
              FROM customers
        ", 'Checklisten-Daten aus customers migriert');
    } else {
        // Leere Einträge für alle Kunden anlegen
        exec_sql($pdo, "
            INSERT IGNORE INTO customer_checklists (customer_id)
            SELECT id FROM customers
        ", 'Leere Checklisten-Einträge angelegt');
    }
} else {
    skip('customer_checklists existiert bereits');
    // Fehlende Einträge für neue Kunden anlegen
    exec_sql($pdo, "
        INSERT IGNORE INTO customer_checklists (customer_id)
        SELECT id FROM customers WHERE id NOT IN (SELECT customer_id FROM customer_checklists)
    ", 'Fehlende Checklisten-Einträge ergänzt');
}

// todo_seen (ggf. aus todo_seen_by migrieren)
if (!tbl_exists($pdo, $db, 'todo_seen')) {
    exec_sql($pdo, "
        CREATE TABLE todo_seen (
          todo_id VARCHAR(64) NOT NULL,
          user_id VARCHAR(64) NOT NULL,
          seen_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (todo_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", 'todo_seen erstellt');

    if (tbl_exists($pdo, $db, 'todo_seen_by')) {
        exec_sql($pdo, "
            INSERT IGNORE INTO todo_seen (todo_id, user_id, seen_at)
            SELECT todo_id, user_id, COALESCE(seen_at, CURRENT_TIMESTAMP)
              FROM todo_seen_by
        ", 'Daten aus todo_seen_by migriert');
    }
} else {
    skip('todo_seen existiert bereits');
}

// ============================================================
// BLOCK B – Fehlende Spalten in users
// ============================================================
sect('Block B – Spalten in users');

if (!col_exists($pdo, $db, 'users', 'avatar_color')) {
    exec_sql($pdo, "ALTER TABLE users ADD COLUMN avatar_color VARCHAR(7) NULL DEFAULT NULL", 'users.avatar_color hinzugefügt');
} else {
    skip('users.avatar_color existiert bereits');
}

if (!col_exists($pdo, $db, 'users', 'avatar_image')) {
    exec_sql($pdo, "ALTER TABLE users ADD COLUMN avatar_image TEXT NULL DEFAULT NULL", 'users.avatar_image hinzugefügt');
} else {
    skip('users.avatar_image existiert bereits');
}

if (!col_exists($pdo, $db, 'users', 'calendar_prefs')) {
    exec_sql($pdo, "ALTER TABLE users ADD COLUMN calendar_prefs JSON NULL DEFAULT NULL", 'users.calendar_prefs hinzugefügt');
} else {
    skip('users.calendar_prefs existiert bereits');
}

if (!col_exists($pdo, $db, 'users', 'updated_at')) {
    exec_sql($pdo, "ALTER TABLE users ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'users.updated_at hinzugefügt');
} else {
    skip('users.updated_at existiert bereits');
}

// ============================================================
// BLOCK C – Spalten-Umbenennungen / Erweiterungen
// ============================================================
sect('Block C – Spalten-Ergänzungen');

// customers.package_name (Umbenennung von package)
if (!col_exists($pdo, $db, 'customers', 'package_name')) {
    exec_sql($pdo, "ALTER TABLE customers ADD COLUMN package_name VARCHAR(190) NULL DEFAULT NULL", 'customers.package_name hinzugefügt');
    if (col_exists($pdo, $db, 'customers', 'package')) {
        exec_sql($pdo, "UPDATE customers SET package_name = package WHERE package_name IS NULL AND package IS NOT NULL", 'Daten von customers.package → package_name kopiert');
    }
} else {
    skip('customers.package_name existiert bereits');
    if (col_exists($pdo, $db, 'customers', 'package')) {
        exec_sql($pdo, "UPDATE customers SET package_name = package WHERE package_name IS NULL AND package IS NOT NULL", 'Fehlende package_name-Werte aus package ergänzt');
    }
}

// customers.billing_same_as_address
if (!col_exists($pdo, $db, 'customers', 'billing_same_as_address')) {
    exec_sql($pdo, "ALTER TABLE customers ADD COLUMN billing_same_as_address TINYINT(1) NOT NULL DEFAULT 0", 'customers.billing_same_as_address hinzugefügt');
} else {
    skip('customers.billing_same_as_address existiert bereits');
}

// customers.updated_at
if (!col_exists($pdo, $db, 'customers', 'updated_at')) {
    exec_sql($pdo, "ALTER TABLE customers ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'customers.updated_at hinzugefügt');
} else {
    skip('customers.updated_at existiert bereits');
}

// customers.pin_hash
if (!col_exists($pdo, $db, 'customers', 'pin_hash')) {
    exec_sql($pdo, "ALTER TABLE customers ADD COLUMN pin_hash VARCHAR(80) NULL DEFAULT NULL", 'customers.pin_hash hinzugefügt');
} else {
    skip('customers.pin_hash existiert bereits');
}

// todos.created_by_id (Umbenennung von created_by)
if (!col_exists($pdo, $db, 'todos', 'created_by_id')) {
    exec_sql($pdo, "ALTER TABLE todos ADD COLUMN created_by_id VARCHAR(64) NULL DEFAULT NULL", 'todos.created_by_id hinzugefügt');
    if (col_exists($pdo, $db, 'todos', 'created_by')) {
        exec_sql($pdo, "UPDATE todos SET created_by_id = created_by WHERE created_by_id IS NULL AND created_by IS NOT NULL", 'Daten von todos.created_by → created_by_id kopiert');
    }
} else {
    skip('todos.created_by_id existiert bereits');
    if (col_exists($pdo, $db, 'todos', 'created_by')) {
        exec_sql($pdo, "UPDATE todos SET created_by_id = created_by WHERE created_by_id IS NULL AND created_by IS NOT NULL", 'Fehlende created_by_id-Werte aus created_by ergänzt');
    }
}

// todos.updated_at
if (!col_exists($pdo, $db, 'todos', 'updated_at')) {
    exec_sql($pdo, "ALTER TABLE todos ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'todos.updated_at hinzugefügt');
} else {
    skip('todos.updated_at existiert bereits');
}

// projects.updated_at (wird von dashboard.php für Änderungserkennung benötigt)
if (!col_exists($pdo, $db, 'projects', 'updated_at')) {
    exec_sql($pdo, "ALTER TABLE projects ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", 'projects.updated_at hinzugefügt');
} else {
    skip('projects.updated_at existiert bereits');
}

// customers: instagram → social_instagram  (alter Spaltenname ohne Präfix)
if (col_exists($pdo, $db, 'customers', 'instagram') && !col_exists($pdo, $db, 'customers', 'social_instagram')) {
    exec_sql($pdo, "ALTER TABLE customers CHANGE COLUMN instagram social_instagram VARCHAR(190) NULL DEFAULT NULL", 'customers.instagram → social_instagram umbenannt');
} elseif (!col_exists($pdo, $db, 'customers', 'social_instagram')) {
    exec_sql($pdo, "ALTER TABLE customers ADD COLUMN social_instagram VARCHAR(190) NULL DEFAULT NULL", 'customers.social_instagram hinzugefügt');
} else {
    skip('customers.social_instagram existiert bereits');
}

// customers: tiktok → social_tiktok
if (col_exists($pdo, $db, 'customers', 'tiktok') && !col_exists($pdo, $db, 'customers', 'social_tiktok')) {
    exec_sql($pdo, "ALTER TABLE customers CHANGE COLUMN tiktok social_tiktok VARCHAR(190) NULL DEFAULT NULL", 'customers.tiktok → social_tiktok umbenannt');
} elseif (!col_exists($pdo, $db, 'customers', 'social_tiktok')) {
    exec_sql($pdo, "ALTER TABLE customers ADD COLUMN social_tiktok VARCHAR(190) NULL DEFAULT NULL", 'customers.social_tiktok hinzugefügt');
} else {
    skip('customers.social_tiktok existiert bereits');
}

// ============================================================
// BLOCK D – User-Seed sicherstellen
// ============================================================
sect('Block D – Kern-Benutzer sicherstellen');

$nameCol = 'name';
if (col_exists($pdo, $db, 'users', 'full_name')) {
    $nameCol = 'full_name';
}

$seedUsers = [
    // password_hash = '' → Erst-Login ohne Passwort (Nutzer legt es selbst beim ersten Login fest)
    ['u_raphael',  'Raphael',  'Raphael Dodidis',      'kontakt@dodidis-media.de', 'sha256$d59642bccca172e785cd7eb3df7cd5ef0943da07d999476ab4afe5f19362a53b'],
    ['u_f2j54ck',  'Leonie',   'Leonie Stella Christ',  null,                       'sha256$261e470e0307050ae4925409616e322903f6824bfba2cfacfb61a8d8384cdf56'],
    ['u_06hdf5r',  'Timo',     'Timo Block',             'kontakt@dodidis-media.de', 'sha256$23383b44e0049f2bc3bb0ed5823581700aaa92c59a84c8b7041665e16345bec6'],
    ['u_163qsdr',  'Dennis',   'Dennis Ryhs',            null,                       'sha256$b56f3b8a7b87e7f880091bc76972621b62fa75039688a6585f3c9ac11b1b0891'],
    ['u_qof4x9n',  'Gokilian', 'Gokilian',               null,                       'sha256$cac3f56ce5bfd492abf5bc7782eb92e80e9d71b3e8290dcec084f2df8c5f5289'],
    ['u_uya5a9e',  'Maxim',    'Maxim Tokarski',         null,                       ''],
];

$stmtU = $pdo->prepare(
    "INSERT INTO users (id, username, {$nameCol}, email, password_hash)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       username   = VALUES(username),
       {$nameCol} = VALUES({$nameCol}),
       email      = VALUES(email),
       password_hash = IF(
           password_hash IS NOT NULL AND password_hash != '' AND VALUES(password_hash) = '',
           password_hash,
           VALUES(password_hash)
       )"
);
$seedOk = 0;
foreach ($seedUsers as [$id, $uname, $name, $email, $hash]) {
    try {
        $stmtU->execute([$id, $uname, $name, $email, $hash]);
        $seedOk++;
    } catch (Throwable $e) {
        err("User $uname: " . $e->getMessage());
    }
}
ok("$seedOk Kern-Benutzer eingespielt");

$seedRoles = [
    ['u_raphael',  'admin'],
    ['u_raphael',  'manager'],
    ['u_raphael',  'videograf'],
    ['u_raphael',  'cutter'],
    ['u_f2j54ck',  'cutter'],
    ['u_06hdf5r',  'manager'],
    ['u_06hdf5r',  'videograf'],
    ['u_06hdf5r',  'cutter'],
    ['u_163qsdr',  'cutter'],
    ['u_qof4x9n',  'cutter'],
    ['u_uya5a9e',  'mitarbeiter'],
];

if (tbl_exists($pdo, $db, 'user_roles')) {
    $stmtR = $pdo->prepare(
        "INSERT IGNORE INTO user_roles (user_id, role_name) VALUES (?, ?)"
    );
    $rolesOk = 0;
    foreach ($seedRoles as [$uid, $role]) {
        try {
            $stmtR->execute([$uid, $role]);
            $rolesOk++;
        } catch (Throwable $e) {
            err("Rolle $uid/$role: " . $e->getMessage());
        }
    }
    ok("Rollen sichergestellt");
} else {
    err('user_roles Tabelle fehlt – bitte install.sql manuell ausführen');
}

// ============================================================
sect('Block F – App-Daten aus LocalStorage einpflegen');

$pdo->exec('SET FOREIGN_KEY_CHECKS=0');

// -- Drehtage (müssen vor Projekten existieren) --
$stmtSD = $pdo->prepare(
    "INSERT IGNORE INTO shoot_days (id, date, videograf_id, start_time, end_time, customer_id, note, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$seedShootDays = [
    ['sd_pnnj1ja','2026-05-20','u_raphael','09:00:00','12:00:00','c_g1qg7ew',
     'Timo & Raphael - Monatscontent 05.-06. 2026','2026-05-19 12:54:12'],
];
$sdOk = 0;
foreach ($seedShootDays as $r) {
    try { $stmtSD->execute($r); $sdOk++; } catch (Throwable $e) { err("ShootDay {$r[0]}: ".$e->getMessage()); }
}
ok("$sdOk Drehtage eingespielt");

// -- Projekte --
$stmtP = $pdo->prepare(
    "INSERT IGNORE INTO projects
       (id, title, customer_id, videograf_id, cutter_id, shoot_date, shoot_day_id,
        deadline, posting_date, script, status, is_internal, created_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
);
$seedProjects = [
    ['p_3e91q09','Anbau Detail','c_g1qg7ew','u_raphael','u_06hdf5r',
     '2026-05-20','sd_pnnj1ja','2026-06-01','2026-06-03',
     "B Roll von dem Anbau Filmen\n\nÄsthetische Shorts in Kombination mit schönem motions Gimbal dazu atmosphärischer Musik",
     'skript',0,'2026-05-19 12:57:16'],
    ['p_qzwt7fs','Onboardingprozess im Video erklärt','c_g1qg7ew','u_raphael','u_163qsdr',
     '2026-05-20','sd_pnnj1ja','2026-06-05','2026-06-07',
     "Kein festes Skript. Wir filmen von A bis Z den Onboarding Prozess von der ersten Minute im Blu bis zum ersten finalen Training dort was für Schritte werden durchlaufen.\nCheck Mescan zum Beispiel.",
     'skript',0,'2026-05-19 13:40:44'],
    ['p_isamf67','Fahrtweg -> Metime','c_g1qg7ew','u_raphael','u_163qsdr',
     '2026-05-20','sd_pnnj1ja','2026-06-05','2026-06-10',
     '','skript',0,'2026-05-19 13:42:23'],
    ['p_k6ibbj9','Ibiza - Coco Beach After Movie','c_mudcxh1','u_raphael','u_raphael',
     '2026-05-23',null,'2026-05-22','2026-05-24',
     'Modern viele Effekte die Energie im Coco Beach zeigen','skript',0,'2026-05-19 17:10:09'],
];
$pOk = 0;
foreach ($seedProjects as $r) {
    try { $stmtP->execute($r); $pOk++; } catch (Throwable $e) { err("Projekt {$r[0]}: ".$e->getMessage()); }
}
ok("$pOk Projekte eingespielt");

// -- Urlaube --
$stmtV = $pdo->prepare(
    "INSERT IGNORE INTO vacations (id, user_id, start_date, end_date, note) VALUES (?,?,?,?,?)"
);
$seedVacations = [
    ['v_dlxvq03','u_raphael', '2026-06-11','2026-06-14',''],
    ['v_mle0bqp','u_163qsdr','2026-05-26','2026-06-02',''],
    ['v_aufp9zn','u_163qsdr','2026-06-25','2026-07-02',''],
    ['v_fashv2v','u_raphael', '2026-05-29','2026-05-29',''],
];
$vOk = 0;
foreach ($seedVacations as $r) {
    try { $stmtV->execute($r); $vOk++; } catch (Throwable $e) { err("Urlaub {$r[0]}: ".$e->getMessage()); }
}
ok("$vOk Urlaube eingespielt");

// -- Todos --
$stmtT = $pdo->prepare(
    "INSERT IGNORE INTO todos (id, title, description, created_by_id, status, created_at)
     VALUES (?,?,?,?,?,?)"
);
$seedTodos = [
    ['t_09fnzwh','Projekte Strukturieren und in neues Portal einbetten','','u_raphael','open','2026-05-19 00:50:00'],
    ['t_13t6rcj','Webseite Fertigstellen',                               '','u_raphael','open','2026-05-19 00:50:33'],
    ['t_1zc57ra','Orga Tool auf Domain bringen',                         '','u_raphael','open','2026-05-19 00:50:48'],
    ['t_oshg99c','Add online Bringen',                                   '','u_raphael','open','2026-05-19 14:10:20'],
    ['t_cq3kp7s','Landing Page für Add',                                 '','u_raphael','open','2026-05-19 14:10:32'],
];
$tOk = 0;
foreach ($seedTodos as $r) {
    try { $stmtT->execute($r); $tOk++; } catch (Throwable $e) { err("Todo {$r[0]}: ".$e->getMessage()); }
}
ok("$tOk Todos eingespielt");

// -- Todo-Zuweisungen --
if (tbl_exists($pdo, $db, 'todo_assignees')) {
    $stmtTA = $pdo->prepare("INSERT IGNORE INTO todo_assignees (todo_id, user_id) VALUES (?,?)");
    $seedTA = [
        ['t_09fnzwh','u_raphael'],['t_09fnzwh','u_06hdf5r'],
        ['t_13t6rcj','u_uya5a9e'],
        ['t_1zc57ra','u_raphael'],['t_1zc57ra','u_uya5a9e'],
        ['t_oshg99c','u_06hdf5r'],
        ['t_cq3kp7s','u_06hdf5r'],['t_cq3kp7s','u_uya5a9e'],
    ];
    $taOk = 0;
    foreach ($seedTA as [$tid,$uid]) {
        try { $stmtTA->execute([$tid,$uid]); $taOk++; } catch (Throwable $e) { err("TA $tid/$uid: ".$e->getMessage()); }
    }
    ok("$taOk Todo-Zuweisungen eingespielt");
} else { skip('todo_assignees nicht vorhanden – übersprungen'); }

// -- Todo-gelesen --
if (tbl_exists($pdo, $db, 'todo_seen')) {
    $stmtTS = $pdo->prepare("INSERT IGNORE INTO todo_seen (todo_id, user_id) VALUES (?,?)");
    $seedTS = [
        ['t_09fnzwh','u_raphael'],['t_09fnzwh','u_06hdf5r'],
        ['t_13t6rcj','u_raphael'],
        ['t_1zc57ra','u_raphael'],
        ['t_oshg99c','u_raphael'],['t_oshg99c','u_06hdf5r'],
        ['t_cq3kp7s','u_raphael'],['t_cq3kp7s','u_06hdf5r'],
    ];
    $tsOk = 0;
    foreach ($seedTS as [$tid,$uid]) {
        try { $stmtTS->execute([$tid,$uid]); $tsOk++; } catch (Throwable $e) { err("TS $tid/$uid: ".$e->getMessage()); }
    }
    ok("$tsOk Todo-gelesen eingespielt");
} else { skip('todo_seen nicht vorhanden – übersprungen'); }

$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
skip('calendarConfig: GitHub-Token wird nicht eingespielt (Security). Bitte nach Login unter Admin → Einstellungen neu eintragen.');

// ============================================================
sect('Block G – customer_files Tabelle');

if (!tbl_exists($pdo, $db, 'customer_files')) {
    exec_sql($pdo, "
        CREATE TABLE customer_files (
          id          BIGINT       NOT NULL AUTO_INCREMENT,
          customer_id VARCHAR(64)  NOT NULL,
          kind        ENUM('vertrag','leistungsbeschreibung','avv','other') NOT NULL DEFAULT 'other',
          filename    VARCHAR(255) NOT NULL,
          mime        VARCHAR(100) NOT NULL,
          size        INT          NOT NULL DEFAULT 0,
          path        VARCHAR(500) NOT NULL,
          uploaded_by VARCHAR(64)  NULL,
          uploaded_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_cf_customer (customer_id),
          CONSTRAINT fk_cf_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
          CONSTRAINT fk_cf_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id)     ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ", 'customer_files erstellt');
} else {
    skip('customer_files existiert bereits');
}

// ============================================================
// Block H – Private Upload-Ordner (außerhalb Webroot)
// ============================================================
sect('Block H – Private Upload-Verzeichnisse anlegen');

$cfg_h = require __DIR__ . '/config.php';
$privatePath = $cfg_h['private_path'] ?? null;

if (!$privatePath) {
    warn('config.php hat keinen private_path definiert – übersprungen.');
} else {
    ok('Ziel-Pfad: ' . $privatePath);
    $dirs = [
        $privatePath,
        $privatePath . '/customers',
        $privatePath . '/projects',
    ];
    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            if (@mkdir($d, 0755, true)) {
                ok('Verzeichnis angelegt: ' . basename($d));
            } else {
                warn('mkdir() fehlgeschlagen für: ' . $d);
                warn('→ Bitte Ordner manuell anlegen und PHP-Schreibrechte setzen.');
            }
        } else {
            skip('Existiert bereits: ' . basename($d));
        }
    }
    // .htaccess schreiben (überschreibt falls veraltet)
    $htaccess = $privatePath . '/.htaccess';
    $htContent = "Order deny,allow\nDeny from all\n";
    if (file_put_contents($htaccess, $htContent) !== false) {
        ok('.htaccess Zugriffsschutz gesetzt');
    } else {
        warn('.htaccess konnte nicht geschrieben werden.');
    }
    // index.php als Fallback
    $idxFile = $privatePath . '/index.php';
    if (!file_exists($idxFile)) {
        file_put_contents($idxFile, "<?php http_response_code(403); exit;\n");
        ok('index.php Fallback erstellt');
    } else {
        skip('index.php bereits vorhanden');
    }
    ok('Nginx-Hinweis: Falls nginx statische Dateien direkt ausliefert, bitte in der Server-Config ergänzen:');
    ok('  location /agenturtool/private_uploads/ { deny all; return 403; }');
}

// ============================================================
// ABSCHLUSS – Tabellenstatus
// ============================================================
sect('Status aller Tabellen');

$tables = [
    'users','user_roles','customers','customer_checklists','customer_files','projects','shoot_days',
    'todos','todo_assignees','todo_seen','vacations',
    'project_files','activity_log','app_config',
];
$allOk = true;
foreach ($tables as $t) {
    try {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        ok("$t ($count Zeilen)");
    } catch (Throwable $e) {
        err("$t FEHLT: " . $e->getMessage());
        $allOk = false;
    }
}

echo "\n";
?>
</pre>
<?php if ($allOk): ?>
<div class="done">
  ✓ Alle Tabellen vorhanden – Migration abgeschlossen.<br>
  <br>
  Nächste Schritte:<br>
  1. <a class="link" href="db-test.php">db-test.php</a> aufrufen und alle Tabellen prüfen<br>
  2. <a class="link" href="index.php">App öffnen</a> – Daten sollten jetzt erscheinen<br>
  3. Diese Datei kann danach gelöscht werden (run_migrations.php)
</div>
<?php else: ?>
<div class="done" style="background:rgba(255,159,10,.1);border-color:rgba(255,159,10,.3);color:#ff9f0a;">
  ⚠ Einige Tabellen fehlen noch – bitte Fehlermeldungen oben prüfen.<br>
  Ggf. <code>install.sql</code> manuell via phpMyAdmin ausführen.
</div>
<?php endif; ?>
</body>
</html>
