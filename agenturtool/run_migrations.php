<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/response.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth-check.php';

start_app_session();

if (empty($_SESSION['uid']) || !in_array('admin', (array)($_SESSION['roles'] ?? []), true)) {
    header('Location: login.php');
    exit;
}

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$results = [];

function colExists(PDO $pdo, string $table, string $col): bool {
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $s  = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?");
    $s->execute([$db, $table, $col]);
    return (int)$s->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool {
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $s  = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?");
    $s->execute([$db, $table]);
    return (int)$s->fetchColumn() > 0;
}

function step(PDO $pdo, string $label, string $sql, array &$out): void {
    try {
        $pdo->exec($sql);
        $out[] = ['ok' => true, 'label' => $label];
    } catch (Throwable $e) {
        $out[] = ['ok' => false, 'label' => $label, 'err' => $e->getMessage()];
    }
}

function addCol(PDO $pdo, string $table, string $col, string $sql, array &$out): void {
    if (!colExists($pdo, $table, $col)) {
        step($pdo, "{$table}.{$col} hinzufügen", $sql, $out);
    } else {
        $out[] = ['ok' => true, 'label' => "{$table}.{$col} bereits vorhanden"];
    }
}

// ── 1. Leere Rolle entfernen (verursacht "undefined" in der UI) ──────────────
step($pdo, "user_roles: leere Einträge löschen",
    "DELETE FROM user_roles WHERE role_name = ''", $results);

// ── 2. user_roles ENUM erweitern ─────────────────────────────────────────────
step($pdo, "user_roles: ENUM contract_uploader ergänzen",
    "ALTER TABLE user_roles MODIFY COLUMN role_name
     ENUM('admin','manager','videograf','cutter','mitarbeiter','contract_uploader') NOT NULL",
    $results);

// ── 3. customer_files: fehlende Spalten ──────────────────────────────────────
addCol($pdo, 'customer_files', 'kind',
    "ALTER TABLE customer_files ADD COLUMN kind ENUM('vertrag','leistungsbeschreibung','avv','other') NOT NULL DEFAULT 'other'", $results);
addCol($pdo, 'customer_files', 'filename',
    "ALTER TABLE customer_files ADD COLUMN filename VARCHAR(255) NOT NULL DEFAULT ''", $results);
addCol($pdo, 'customer_files', 'mime',
    "ALTER TABLE customer_files ADD COLUMN mime VARCHAR(100) NOT NULL DEFAULT ''", $results);
addCol($pdo, 'customer_files', 'size',
    "ALTER TABLE customer_files ADD COLUMN size INT NOT NULL DEFAULT 0", $results);
addCol($pdo, 'customer_files', 'path',
    "ALTER TABLE customer_files ADD COLUMN path VARCHAR(500) NOT NULL DEFAULT ''", $results);
addCol($pdo, 'customer_files', 'uploaded_by',
    "ALTER TABLE customer_files ADD COLUMN uploaded_by VARCHAR(64) NULL", $results);
addCol($pdo, 'customer_files', 'uploaded_at',
    "ALTER TABLE customer_files ADD COLUMN uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $results);

// ── 4. project_files: fehlende Spalten ───────────────────────────────────────
addCol($pdo, 'project_files', 'kind',
    "ALTER TABLE project_files ADD COLUMN kind ENUM('script','contract','correction','other','rohmaterial','fertigstellung') NOT NULL DEFAULT 'other'", $results);
addCol($pdo, 'project_files', 'filename',
    "ALTER TABLE project_files ADD COLUMN filename VARCHAR(255) NOT NULL DEFAULT ''", $results);
addCol($pdo, 'project_files', 'mime',
    "ALTER TABLE project_files ADD COLUMN mime VARCHAR(96) NOT NULL DEFAULT ''", $results);
addCol($pdo, 'project_files', 'size',
    "ALTER TABLE project_files ADD COLUMN size INT NOT NULL DEFAULT 0", $results);
addCol($pdo, 'project_files', 'path',
    "ALTER TABLE project_files ADD COLUMN path VARCHAR(500) NOT NULL DEFAULT ''", $results);
addCol($pdo, 'project_files', 'uploaded_by',
    "ALTER TABLE project_files ADD COLUMN uploaded_by VARCHAR(64) NULL", $results);
addCol($pdo, 'project_files', 'uploaded_at',
    "ALTER TABLE project_files ADD COLUMN uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $results);

// ENUM erweitern (falls kind schon existierte aber mit altem ENUM)
step($pdo, "project_files.kind ENUM aktualisieren",
    "ALTER TABLE project_files MODIFY COLUMN kind ENUM('script','contract','correction','other','rohmaterial','fertigstellung') NOT NULL DEFAULT 'other'",
    $results);

// ── 4b. shoot_days: rescheduled_from Spalte ──────────────────────────────────
addCol($pdo, 'shoot_days', 'rescheduled_from',
    "ALTER TABLE shoot_days ADD COLUMN rescheduled_from DATE NULL", $results);

// ── 5. contracts Tabelle ──────────────────────────────────────────────────────
if (!tableExists($pdo, 'contracts')) {
    step($pdo, "contracts: Tabelle anlegen",
        "CREATE TABLE contracts (
           id VARCHAR(64) NOT NULL, customer_id VARCHAR(64) NULL,
           title VARCHAR(255) NOT NULL,
           status ENUM('draft','confirmed') NOT NULL DEFAULT 'draft',
           filename VARCHAR(255) NULL, mime VARCHAR(96) NULL,
           size INT UNSIGNED NULL, path VARCHAR(500) NULL,
           uploaded_by VARCHAR(64) NULL,
           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
           PRIMARY KEY (id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "contracts: Tabelle bereits vorhanden"];
    addCol($pdo, 'contracts', 'filename',    "ALTER TABLE contracts ADD COLUMN filename VARCHAR(255) NULL", $results);
    addCol($pdo, 'contracts', 'mime',        "ALTER TABLE contracts ADD COLUMN mime VARCHAR(96) NULL", $results);
    addCol($pdo, 'contracts', 'size',        "ALTER TABLE contracts ADD COLUMN size INT UNSIGNED NULL", $results);
    addCol($pdo, 'contracts', 'path',        "ALTER TABLE contracts ADD COLUMN path VARCHAR(500) NULL", $results);
    addCol($pdo, 'contracts', 'uploaded_by', "ALTER TABLE contracts ADD COLUMN uploaded_by VARCHAR(64) NULL", $results);
    addCol($pdo, 'contracts', 'updated_at',  "ALTER TABLE contracts ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP", $results);
}

// ── 5b. contracts: digitale Unterschrift (Status-Flow + Signatur-/Audit-/Final-Felder) ──
// Status-ENUM um 'awaiting_signature' und 'signed' erweitern (idempotent für Bestand + Neuanlage).
step($pdo, "contracts.status ENUM erweitern (awaiting_signature, signed)",
    "ALTER TABLE contracts MODIFY COLUMN status ENUM('draft','confirmed','awaiting_signature','signed') NOT NULL DEFAULT 'draft'",
    $results);
// Agentur-Vorsignatur
addCol($pdo, 'contracts', 'agency_signed_by',    "ALTER TABLE contracts ADD COLUMN agency_signed_by VARCHAR(64) NULL", $results);
addCol($pdo, 'contracts', 'agency_signer_name',  "ALTER TABLE contracts ADD COLUMN agency_signer_name VARCHAR(255) NULL", $results);
addCol($pdo, 'contracts', 'agency_signed_at',    "ALTER TABLE contracts ADD COLUMN agency_signed_at DATETIME NULL", $results);
addCol($pdo, 'contracts', 'agency_signature',    "ALTER TABLE contracts ADD COLUMN agency_signature MEDIUMTEXT NULL", $results);
// Kunden-Signatur + Audit
addCol($pdo, 'contracts', 'customer_signed_at',  "ALTER TABLE contracts ADD COLUMN customer_signed_at DATETIME NULL", $results);
addCol($pdo, 'contracts', 'customer_signer_name',"ALTER TABLE contracts ADD COLUMN customer_signer_name VARCHAR(255) NULL", $results);
addCol($pdo, 'contracts', 'customer_signer_ip',  "ALTER TABLE contracts ADD COLUMN customer_signer_ip VARCHAR(64) NULL", $results);
addCol($pdo, 'contracts', 'customer_consent_at', "ALTER TABLE contracts ADD COLUMN customer_consent_at DATETIME NULL", $results);
addCol($pdo, 'contracts', 'customer_signature',  "ALTER TABLE contracts ADD COLUMN customer_signature MEDIUMTEXT NULL", $results);
// Fertiges, unterschriebenes Dokument
addCol($pdo, 'contracts', 'signed_filename',     "ALTER TABLE contracts ADD COLUMN signed_filename VARCHAR(255) NULL", $results);
addCol($pdo, 'contracts', 'signed_mime',         "ALTER TABLE contracts ADD COLUMN signed_mime VARCHAR(96) NULL", $results);
addCol($pdo, 'contracts', 'signed_size',         "ALTER TABLE contracts ADD COLUMN signed_size INT UNSIGNED NULL", $results);
addCol($pdo, 'contracts', 'signed_path',         "ALTER TABLE contracts ADD COLUMN signed_path VARCHAR(500) NULL", $results);
addCol($pdo, 'contracts', 'signed_hash',         "ALTER TABLE contracts ADD COLUMN signed_hash VARCHAR(64) NULL", $results);
addCol($pdo, 'contracts', 'original_hash',       "ALTER TABLE contracts ADD COLUMN original_hash VARCHAR(64) NULL", $results);
addCol($pdo, 'contracts', 'signed_at',           "ALTER TABLE contracts ADD COLUMN signed_at DATETIME NULL", $results);

// ── 6. contract_comments ─────────────────────────────────────────────────────
if (!tableExists($pdo, 'contract_comments')) {
    step($pdo, "contract_comments: Tabelle anlegen",
        "CREATE TABLE contract_comments (
           id VARCHAR(64) NOT NULL, contract_id VARCHAR(64) NOT NULL,
           user_id VARCHAR(64) NULL, comment_text TEXT NULL,
           voice_path VARCHAR(500) NULL, voice_filename VARCHAR(255) NULL,
           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id), KEY idx_cc_contract (contract_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "contract_comments: bereits vorhanden"];
}
// Sprachnachricht-MIME persistieren (iOS liefert mp4 statt webm)
addCol($pdo, 'contract_comments', 'mime', "ALTER TABLE contract_comments ADD COLUMN mime VARCHAR(96) NULL", $results);

// ── 7. notifications ──────────────────────────────────────────────────────────
if (!tableExists($pdo, 'notifications')) {
    step($pdo, "notifications: Tabelle anlegen",
        "CREATE TABLE notifications (
           id BIGINT NOT NULL AUTO_INCREMENT, user_id VARCHAR(64) NOT NULL,
           type VARCHAR(64) NOT NULL, title VARCHAR(255) NULL, body TEXT NULL,
           ref_id VARCHAR(64) NULL, ref_type VARCHAR(32) NULL, seen_at DATETIME NULL,
           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id), KEY idx_notif_user (user_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    // Ältere Installs haben ggf. read_at statt seen_at – umbenennen
    if (colExists($pdo, 'notifications', 'read_at') && !colExists($pdo, 'notifications', 'seen_at')) {
        step($pdo, "notifications: read_at → seen_at umbenennen",
            "ALTER TABLE notifications CHANGE COLUMN read_at seen_at DATETIME NULL",
            $results);
    } elseif (!colExists($pdo, 'notifications', 'seen_at')) {
        step($pdo, "notifications: seen_at-Spalte ergänzen",
            "ALTER TABLE notifications ADD COLUMN seen_at DATETIME NULL",
            $results);
    } else {
        $results[] = ['ok' => true, 'label' => "notifications: bereits vorhanden"];
    }
}

// ── 8. activity_log ───────────────────────────────────────────────────────────
if (!tableExists($pdo, 'activity_log')) {
    step($pdo, "activity_log: Tabelle anlegen",
        "CREATE TABLE activity_log (
           id BIGINT NOT NULL AUTO_INCREMENT, ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           scope VARCHAR(32) NOT NULL, ref_id VARCHAR(64) NULL, action VARCHAR(64) NOT NULL,
           actor_id VARCHAR(64) NULL, details JSON NULL,
           PRIMARY KEY (id), KEY idx_al_ref (scope, ref_id), KEY idx_al_ts (ts)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "activity_log: bereits vorhanden"];
}

// ── 9. app_config ─────────────────────────────────────────────────────────────
if (!tableExists($pdo, 'app_config')) {
    step($pdo, "app_config: Tabelle anlegen",
        "CREATE TABLE app_config (
           `key` VARCHAR(64) NOT NULL, value JSON NOT NULL,
           updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
           PRIMARY KEY (`key`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "app_config: bereits vorhanden"];
}

// ── 10. project_shootdate_history ─────────────────────────────────────────────
if (!tableExists($pdo, 'project_shootdate_history')) {
    step($pdo, "project_shootdate_history: Tabelle anlegen",
        "CREATE TABLE project_shootdate_history (
           id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
           project_id VARCHAR(64) NOT NULL,
           old_shoot_date DATE NOT NULL,
           new_shoot_date DATE NOT NULL,
           changed_by VARCHAR(64) NULL,
           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id), KEY idx_psh_project (project_id),
           KEY idx_psh_old_date (old_shoot_date), KEY idx_psh_new_date (new_shoot_date)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "project_shootdate_history: bereits vorhanden"];
}

// ── 11. customer_checklists (wird von api.php?action=pull per LEFT JOIN benötigt) ──
if (!tableExists($pdo, 'customer_checklists')) {
    step($pdo, "customer_checklists: Tabelle anlegen",
        "CREATE TABLE customer_checklists (
           customer_id      VARCHAR(64) NOT NULL,
           contract_signed  TINYINT(1)  NOT NULL DEFAULT 0,
           deposit_received TINYINT(1)  NOT NULL DEFAULT 0,
           kickoff_done     TINYINT(1)  NOT NULL DEFAULT 0,
           social_access    TINYINT(1)  NOT NULL DEFAULT 0,
           first_shoot      TINYINT(1)  NOT NULL DEFAULT 0,
           PRIMARY KEY (customer_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "customer_checklists: bereits vorhanden"];
}

// ── 12. todo_seen (wird von api.php?action=pull abgefragt) ────────────────────
if (!tableExists($pdo, 'todo_seen')) {
    step($pdo, "todo_seen: Tabelle anlegen",
        "CREATE TABLE todo_seen (
           todo_id VARCHAR(64) NOT NULL,
           user_id VARCHAR(64) NOT NULL,
           seen_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (todo_id, user_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "todo_seen: bereits vorhanden"];
}

// ── 13. activity_log: ts-Spalte sicherstellen ─────────────────────────────────
// install.sql nutzt 'ts', älteres run_migrations.php hat 'created_at' angelegt.
if (tableExists($pdo, 'activity_log')) {
    if (!colExists($pdo, 'activity_log', 'ts') && colExists($pdo, 'activity_log', 'created_at')) {
        step($pdo, "activity_log: created_at → ts umbenennen",
            "ALTER TABLE activity_log CHANGE COLUMN created_at ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            $results);
    } elseif (!colExists($pdo, 'activity_log', 'ts')) {
        addCol($pdo, 'activity_log', 'ts',
            "ALTER TABLE activity_log ADD COLUMN ts DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            $results);
    } else {
        $results[] = ['ok' => true, 'label' => "activity_log.ts: bereits vorhanden"];
    }
} else {
    // Tabelle existiert noch nicht – wird oben in Schritt 8 angelegt, mit ts statt created_at
    $results[] = ['ok' => true, 'label' => "activity_log: wird mit ts-Spalte angelegt (Schritt 8 erledigt)"];
}

// ── 14. users: fehlende Spalten ───────────────────────────────────────────────
addCol($pdo, 'users', 'avatar_color',
    "ALTER TABLE users ADD COLUMN avatar_color VARCHAR(32) NULL", $results);
addCol($pdo, 'users', 'avatar_image',
    "ALTER TABLE users ADD COLUMN avatar_image MEDIUMTEXT NULL", $results);
addCol($pdo, 'users', 'calendar_prefs',
    "ALTER TABLE users ADD COLUMN calendar_prefs JSON NULL", $results);
addCol($pdo, 'users', 'calendar_token',
    "ALTER TABLE users ADD COLUMN calendar_token VARCHAR(64) NULL", $results);

// ── 15. customers: package_name Spalte ───────────────────────────────────────
if (!colExists($pdo, 'customers', 'package_name')) {
    step($pdo, "customers.package_name hinzufügen",
        "ALTER TABLE customers ADD COLUMN package_name VARCHAR(255) NULL", $results);
    // Falls alte Spalte 'package' existiert, Daten übernehmen
    if (colExists($pdo, 'customers', 'package')) {
        step($pdo, "customers.package → package_name kopieren",
            "UPDATE customers SET package_name = `package` WHERE package_name IS NULL", $results);
    }
} else {
    $results[] = ['ok' => true, 'label' => "customers.package_name: bereits vorhanden"];
}

// ── 16. todos: created_by_id Spalte ──────────────────────────────────────────
if (!colExists($pdo, 'todos', 'created_by_id')) {
    addCol($pdo, 'todos', 'created_by_id',
        "ALTER TABLE todos ADD COLUMN created_by_id VARCHAR(64) NULL", $results);
    // Falls alte Spalte 'created_by' existiert, Daten übernehmen
    if (colExists($pdo, 'todos', 'created_by')) {
        step($pdo, "todos.created_by → created_by_id kopieren",
            "UPDATE todos SET created_by_id = created_by WHERE created_by_id IS NULL", $results);
    }
} else {
    $results[] = ['ok' => true, 'label' => "todos.created_by_id: bereits vorhanden"];
}

// ── 17. projects: alte JSON-Spalte → individuelle Spalten migrieren ───────────
// Wenn 'data'-Spalte existiert aber 'title' fehlt → altes Schema → Spalten ergänzen + Daten migrieren.
if (colExists($pdo, 'projects', 'data') && !colExists($pdo, 'projects', 'title')) {
    $projCols = [
        'title'        => "ALTER TABLE projects ADD COLUMN title VARCHAR(190) NOT NULL DEFAULT ''",
        'customer_id'  => "ALTER TABLE projects ADD COLUMN customer_id VARCHAR(64) NULL",
        'videograf_id' => "ALTER TABLE projects ADD COLUMN videograf_id VARCHAR(64) NULL",
        'cutter_id'    => "ALTER TABLE projects ADD COLUMN cutter_id VARCHAR(64) NULL",
        'shoot_date'   => "ALTER TABLE projects ADD COLUMN shoot_date DATE NULL",
        'shoot_day_id' => "ALTER TABLE projects ADD COLUMN shoot_day_id VARCHAR(64) NULL",
        'deadline'     => "ALTER TABLE projects ADD COLUMN deadline DATE NULL",
        'posting_date' => "ALTER TABLE projects ADD COLUMN posting_date DATE NULL",
        'script'       => "ALTER TABLE projects ADD COLUMN script TEXT NULL",
        'status'       => "ALTER TABLE projects ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'skript'",
        'is_internal'  => "ALTER TABLE projects ADD COLUMN is_internal TINYINT(1) NOT NULL DEFAULT 0",
        'approved_at'  => "ALTER TABLE projects ADD COLUMN approved_at DATETIME NULL",
        'created_at'   => "ALTER TABLE projects ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
    ];
    foreach ($projCols as $col => $sql) {
        addCol($pdo, 'projects', $col, $sql, $results);
    }
    // Daten via JSON_EXTRACT aus alter data-Spalte übernehmen
    step($pdo, "projects: Daten aus data-JSON in individuelle Spalten migrieren",
        "UPDATE projects SET
            title        = COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.title')), 'null'), ''),
            customer_id  = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.customerId')), 'null'),
            videograf_id = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.videografId')), 'null'),
            cutter_id    = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.cutterId')), 'null'),
            shoot_date   = DATE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.shootDate')), 'null')),
            deadline     = DATE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.deadline')), 'null')),
            posting_date = DATE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.postingDate')), 'null')),
            script       = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(data, '$.script')), 'null'),
            status       = CASE
                WHEN JSON_UNQUOTE(JSON_EXTRACT(data, '$.status'))
                     IN ('skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert')
                THEN JSON_UNQUOTE(JSON_EXTRACT(data, '$.status'))
                ELSE 'skript'
            END,
            is_internal  = IF(JSON_EXTRACT(data, '$.isInternal') = true, 1, 0)
         WHERE data IS NOT NULL",
        $results);
} elseif (!colExists($pdo, 'projects', 'status')) {
    // Neue Schema, aber status-Spalte fehlt noch
    addCol($pdo, 'projects', 'status',
        "ALTER TABLE projects ADD COLUMN status ENUM('idee','skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert') NOT NULL DEFAULT 'skript'",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "projects: individuelle Spalten bereits vorhanden"];
}

// ── 18. projects: updated_at nachrüsten (fehlt im alten Schema-Migrationspfad) ──
addCol($pdo, 'projects', 'updated_at',
    "ALTER TABLE projects ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    $results);

// ── 18b. projects: NAS-Freigabelinks (Korrekturschleife über Ugreen NAS) ──────
// 01_Rohmaterial (read-only Cutter), 03_Exporte_Cutter (Cutter-Upload),
// 04_Freigaben_Manager (finaler Review). Nur Share-Links, keine Dateiübertragung.
addCol($pdo, 'projects', 'nas_rohmaterial_url',
    "ALTER TABLE projects ADD COLUMN nas_rohmaterial_url TEXT NULL", $results);
addCol($pdo, 'projects', 'nas_export_url',
    "ALTER TABLE projects ADD COLUMN nas_export_url TEXT NULL", $results);
addCol($pdo, 'projects', 'nas_freigabe_url',
    "ALTER TABLE projects ADD COLUMN nas_freigabe_url TEXT NULL", $results);

// ── 19. projects: ungültige status-Werte bereinigen + Spalte auf ENUM umstellen ─
step($pdo, "projects: ungültige status-Werte auf 'skript' zurücksetzen",
    "UPDATE projects SET status = 'skript'
     WHERE status = '' OR status IS NULL
        OR status NOT IN ('idee','skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert')",
    $results);

try {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $typeStmt = $pdo->prepare(
        "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'status'"
    );
    $typeStmt->execute([$dbName]);
    $colDef = (string)$typeStmt->fetchColumn();

    $required = ['idee','skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert'];
    $needsUpdate = false;
    foreach ($required as $val) {
        if (strpos($colDef, "'$val'") === false) { $needsUpdate = true; break; }
    }

    if ($needsUpdate) {
        step($pdo, "projects: status-ENUM aktualisieren (fehlende Werte ergänzen)",
            "ALTER TABLE projects MODIFY COLUMN status
             ENUM('idee','skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert')
             NOT NULL DEFAULT 'skript'",
            $results);
    } else {
        $results[] = ['ok' => true, 'label' => "projects.status ENUM-Definition vollständig – keine Änderung nötig"];
    }
} catch (\Throwable $e) {
    $results[] = ['ok' => false, 'label' => "projects.status ENUM-Prüfung fehlgeschlagen: " . $e->getMessage()];
}

// ── 19b. Bestand: alte Brainstorming-Ideen (skript ohne Kunde/Drehdatum) auf 'idee' heben ─
// Macht den heuristisch ermittelten Ideen-Status dauerhaft, ohne echte Skript-Projekte
// (mit Kunde oder Drehdatum) anzufassen.
step($pdo, "projects: bestehende Ideen (skript ohne Kunde/Datum) auf Status 'idee' migrieren",
    "UPDATE projects SET status = 'idee'
      WHERE status = 'skript'
        AND (customer_id IS NULL OR customer_id = '')
        AND shoot_date IS NULL
        AND (shoot_day_id IS NULL OR shoot_day_id = '')
        AND is_internal = 0",
    $results);

// ── 20. vacations: approved_by + approved_at nachrüsten ──────────────────────
addCol($pdo, 'vacations', 'approved_by',
    "ALTER TABLE vacations ADD COLUMN approved_by VARCHAR(64) NULL",
    $results);
addCol($pdo, 'vacations', 'approved_at',
    "ALTER TABLE vacations ADD COLUMN approved_at DATETIME NULL",
    $results);

// ── 21. projects: OPTIMIZE TABLE ─────────────────────────────────────────────
try {
    $pdo->query("OPTIMIZE TABLE projects")->fetchAll();
    $results[] = ['ok' => true, 'label' => "projects: OPTIMIZE TABLE (ENUM-Metadata-Cache leeren)"];
} catch (\Throwable $e) {
    $results[] = ['ok' => false, 'label' => "projects: OPTIMIZE TABLE fehlgeschlagen", 'err' => $e->getMessage()];
}

// ── 22. projects: Trigger-Diagnose ───────────────────────────────────────────
try {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $trigStmt = $pdo->prepare(
        "SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING,
                LEFT(ACTION_STATEMENT, 300) AS action_snippet
           FROM information_schema.TRIGGERS
          WHERE EVENT_OBJECT_SCHEMA = ? AND EVENT_OBJECT_TABLE = 'projects'"
    );
    $trigStmt->execute([$dbName]);
    $triggers = $trigStmt->fetchAll();
    if ($triggers) {
        foreach ($triggers as $t) {
            $results[] = ['ok' => true, 'label' => "HINWEIS – Trigger gefunden: {$t['TRIGGER_NAME']} ({$t['ACTION_TIMING']} {$t['EVENT_MANIPULATION']}): " . $t['action_snippet']];
        }
    } else {
        $results[] = ['ok' => true, 'label' => "projects: keine Trigger gefunden"];
    }
} catch (\Throwable $e) {
    $results[] = ['ok' => false, 'label' => "Trigger-Prüfung fehlgeschlagen", 'err' => $e->getMessage()];
}

// ── 23. project_comments ──────────────────────────────────────────────────────
if (!tableExists($pdo, 'project_comments')) {
    step($pdo, "project_comments: Tabelle anlegen",
        "CREATE TABLE project_comments (
           id            VARCHAR(64)  NOT NULL,
           project_id    VARCHAR(64)  NOT NULL,
           user_id       VARCHAR(64)  NULL,
           comment_text  TEXT         NULL,
           voice_path    VARCHAR(500) NULL,
           voice_filename VARCHAR(255) NULL,
           created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           KEY idx_pcom_project (project_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "project_comments: bereits vorhanden"];
    addCol($pdo, 'project_comments', 'voice_filename',
        "ALTER TABLE project_comments ADD COLUMN voice_filename VARCHAR(255) NULL", $results);
}

// ── 24. content_queue (Social-Media-Content-Queue für n8n-Automation) ─────────
if (!tableExists($pdo, 'content_queue')) {
    step($pdo, "content_queue: Tabelle anlegen",
        "CREATE TABLE content_queue (
           id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
           customer_id       VARCHAR(64)  NULL,
           platform          VARCHAR(50)  NOT NULL DEFAULT 'instagram',
           content_type      VARCHAR(50)  NOT NULL DEFAULT 'story',
           caption           TEXT         NULL,
           media_url         TEXT         NULL,
           status            VARCHAR(50)  NOT NULL DEFAULT 'draft',
           approved_by       VARCHAR(64)  NULL,
           scheduled_at      DATETIME     NULL,
           published_at      DATETIME     NULL,
           platform_response TEXT         NULL,
           error_message     TEXT         NULL,
           created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
           updated_at        DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           KEY idx_cq_due (status, platform, content_type, scheduled_at, published_at),
           KEY idx_cq_customer (customer_id),
           CONSTRAINT fk_cq_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
           CONSTRAINT fk_cq_approver FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "content_queue: bereits vorhanden"];
    // Fehlende Spalten idempotent nachrüsten (falls eine ähnliche Tabelle schon existierte).
    addCol($pdo, 'content_queue', 'customer_id',       "ALTER TABLE content_queue ADD COLUMN customer_id VARCHAR(64) NULL", $results);
    addCol($pdo, 'content_queue', 'platform',          "ALTER TABLE content_queue ADD COLUMN platform VARCHAR(50) NOT NULL DEFAULT 'instagram'", $results);
    addCol($pdo, 'content_queue', 'content_type',      "ALTER TABLE content_queue ADD COLUMN content_type VARCHAR(50) NOT NULL DEFAULT 'story'", $results);
    addCol($pdo, 'content_queue', 'caption',           "ALTER TABLE content_queue ADD COLUMN caption TEXT NULL", $results);
    addCol($pdo, 'content_queue', 'media_url',         "ALTER TABLE content_queue ADD COLUMN media_url TEXT NULL", $results);
    addCol($pdo, 'content_queue', 'status',            "ALTER TABLE content_queue ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'draft'", $results);
    addCol($pdo, 'content_queue', 'approved_by',       "ALTER TABLE content_queue ADD COLUMN approved_by VARCHAR(64) NULL", $results);
    addCol($pdo, 'content_queue', 'scheduled_at',      "ALTER TABLE content_queue ADD COLUMN scheduled_at DATETIME NULL", $results);
    addCol($pdo, 'content_queue', 'published_at',      "ALTER TABLE content_queue ADD COLUMN published_at DATETIME NULL", $results);
    addCol($pdo, 'content_queue', 'platform_response', "ALTER TABLE content_queue ADD COLUMN platform_response TEXT NULL", $results);
    addCol($pdo, 'content_queue', 'error_message',     "ALTER TABLE content_queue ADD COLUMN error_message TEXT NULL", $results);
    addCol($pdo, 'content_queue', 'updated_at',        "ALTER TABLE content_queue ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP", $results);
}

// ── 25. customers: Serien-Einstellungen (Posting-Rhythmus, Standard-Cutter) ──
addCol($pdo, 'customers', 'default_cutter_id',
    "ALTER TABLE customers ADD COLUMN default_cutter_id VARCHAR(64) DEFAULT NULL AFTER videos_per_month",
    $results);
addCol($pdo, 'customers', 'posting_weekdays',
    "ALTER TABLE customers ADD COLUMN posting_weekdays VARCHAR(20) DEFAULT NULL AFTER default_cutter_id",
    $results);
addCol($pdo, 'customers', 'videos_per_week',
    "ALTER TABLE customers ADD COLUMN videos_per_week TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER posting_weekdays",
    $results);
addCol($pdo, 'customers', 'auto_posting_rhythm',
    "ALTER TABLE customers ADD COLUMN auto_posting_rhythm TINYINT(1) NOT NULL DEFAULT 0 AFTER videos_per_week",
    $results);

// ── N. meetings ───────────────────────────────────────────────────────────────
if (!tableExists($pdo, 'meetings')) {
    step($pdo, "meetings: Tabelle anlegen",
        "CREATE TABLE meetings (
           id           VARCHAR(64)   NOT NULL,
           title        VARCHAR(255)  NOT NULL,
           date         DATE          NOT NULL,
           start_time   TIME          NOT NULL DEFAULT '09:00:00',
           end_time     TIME          NULL,
           type         VARCHAR(64)   NOT NULL DEFAULT 'meeting',
           link         VARCHAR(500)  NULL,
           location     VARCHAR(500)  NULL,
           topics       JSON          NULL,
           attendee_ids JSON          NULL,
           customer_id  VARCHAR(64)   NULL,
           created_by   VARCHAR(64)   NULL,
           created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
           updated_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
           PRIMARY KEY (id),
           KEY idx_meetings_date (date),
           KEY idx_meetings_customer (customer_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "meetings: bereits vorhanden"];
    addCol($pdo, 'meetings', 'location',
        "ALTER TABLE meetings ADD COLUMN location VARCHAR(500) NULL AFTER link", $results);
    addCol($pdo, 'meetings', 'topics',
        "ALTER TABLE meetings ADD COLUMN topics JSON NULL AFTER location", $results);
    addCol($pdo, 'meetings', 'attendee_ids',
        "ALTER TABLE meetings ADD COLUMN attendee_ids JSON NULL AFTER topics", $results);
}

$fails = array_values(array_filter($results, fn($r) => !$r['ok']));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Migration</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f0f0f;color:#e5e5e5;padding:24px;max-width:680px;margin:0 auto}
  h1{font-size:20px;font-weight:700;margin-bottom:4px}
  .sub{font-size:13px;color:#888;margin-bottom:20px}
  .banner{border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:14px;font-weight:500}
  .banner.ok {background:rgba(48,209,88,.1);border:1px solid rgba(48,209,88,.3);color:#30d158}
  .banner.err{background:rgba(255,69,58,.1);border:1px solid rgba(255,69,58,.3);color:#ff453a}
  .item{display:flex;align-items:flex-start;gap:10px;padding:8px 12px;border-radius:8px;margin-bottom:5px;font-size:13px}
  .item.ok {background:rgba(48,209,88,.06)}
  .item.err{background:rgba(255,69,58,.1)}
  .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-top:3px}
  .dot.ok {background:#30d158}
  .dot.err{background:#ff453a}
  .errtxt{font-size:11px;color:#ff453a;margin-top:2px}
  .btn{display:inline-block;margin-top:24px;padding:12px 28px;background:#30d158;color:#000;font-weight:700;border-radius:10px;text-decoration:none;font-size:14px}
</style>
</head>
<body>
<h1>Datenbank-Migration</h1>
<div class="sub"><?= count($results) ?> Checks · <?= count($fails) ?> Fehler</div>

<div class="banner <?= count($fails) === 0 ? 'ok' : 'err' ?>">
  <?= count($fails) === 0 ? 'Alle Migrationen erfolgreich — App sollte jetzt fehlerfrei laufen.' : count($fails) . ' Fehler aufgetreten (Details unten).' ?>
</div>

<?php foreach ($results as $r): ?>
<div class="item <?= $r['ok'] ? 'ok' : 'err' ?>">
  <div class="dot <?= $r['ok'] ? 'ok' : 'err' ?>"></div>
  <div>
    <div><?= htmlspecialchars($r['label']) ?></div>
    <?php if (!empty($r['err'])): ?>
      <div class="errtxt"><?= htmlspecialchars($r['err']) ?></div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<a class="btn" href="./">Zurück zur App</a>
</body>
</html>
