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

// ── 7. notifications ──────────────────────────────────────────────────────────
if (!tableExists($pdo, 'notifications')) {
    step($pdo, "notifications: Tabelle anlegen",
        "CREATE TABLE notifications (
           id BIGINT NOT NULL AUTO_INCREMENT, user_id VARCHAR(64) NOT NULL,
           type VARCHAR(64) NOT NULL, title VARCHAR(255) NULL, body TEXT NULL,
           ref_id VARCHAR(64) NULL, ref_type VARCHAR(32) NULL, read_at DATETIME NULL,
           created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id), KEY idx_notif_user (user_id)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        $results);
} else {
    $results[] = ['ok' => true, 'label' => "notifications: bereits vorhanden"];
}

// ── 8. activity_log ───────────────────────────────────────────────────────────
if (!tableExists($pdo, 'activity_log')) {
    step($pdo, "activity_log: Tabelle anlegen",
        "CREATE TABLE activity_log (
           id BIGINT NOT NULL AUTO_INCREMENT, scope VARCHAR(32) NOT NULL,
           ref_id VARCHAR(64) NOT NULL, action VARCHAR(64) NOT NULL,
           actor_id VARCHAR(64) NULL, actor_name VARCHAR(128) NULL,
           details JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (id), KEY idx_al_ref (scope, ref_id)
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

$fails = array_filter($results, fn($r) => !$r['ok']);
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
