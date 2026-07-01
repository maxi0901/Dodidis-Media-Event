<?php
declare(strict_types=1);

/**
 * ROLLBACK — NAS Media Layer (Schritte 26–28 aus run_migrations.php)
 *
 * Entfernt die von der NAS-Medien-Schicht hinzugefügten Tabellen und Spalten.
 * Bestehende Daten in assets / project_cutters gehen verloren!
 *
 * Voraussetzung: Admin-Login (wie run_migrations.php).
 * Aufruf: https://…/agenturtool/rollback_nas_migrations.php
 *
 * Rollback-Reihenfolge (umgekehrt zu run_migrations.php):
 *   28. project_cutters   — Tabelle löschen
 *   27. assets            — Tabelle löschen
 *   26. projects.nas_folder, projects.slug — Spalten entfernen
 *
 * HINWEIS: projects.cutter_id (1:1) bleibt erhalten — wurde vor der NAS-Schicht
 * angelegt und ist Teil des Kernsystems.
 *
 * Nach dem Rollback: neue PHP-Dateien entfernen und Apache neu laden.
 *   rm agenturtool/api/NasWebDAV.php
 *   rm agenturtool/api/access.php
 *   rm agenturtool/api/nas_projects.php
 *   rm agenturtool/api/nas_assets.php
 *   rm agenturtool/api/upload.js
 *   rm agenturtool/api/.user.ini
 *   apachectl graceful   # oder: service apache2 reload
 */

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

$dryRun  = !empty($_GET['dry']);
$execute = !empty($_GET['confirm']) && !$dryRun;
$results = [];

function rb_step(PDO $pdo, string $label, string $sql, array &$out, bool $dryRun, bool $execute): void {
    if ($dryRun) {
        $out[] = ['ok' => true, 'label' => "[DRY-RUN] {$label}", 'sql' => $sql];
        return;
    }
    if (!$execute) {
        $out[] = ['ok' => true, 'label' => "[PREVIEW] {$label} — SQL: " . substr($sql, 0, 120)];
        return;
    }
    try {
        $pdo->exec($sql);
        $out[] = ['ok' => true, 'label' => $label];
    } catch (\Throwable $e) {
        $out[] = ['ok' => false, 'label' => $label, 'err' => $e->getMessage()];
    }
}

function rb_col_exists(PDO $pdo, string $table, string $col): bool {
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $s  = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?");
    $s->execute([$db, $table, $col]);
    return (int)$s->fetchColumn() > 0;
}

function rb_table_exists(PDO $pdo, string $table): bool {
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $s  = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?");
    $s->execute([$db, $table]);
    return (int)$s->fetchColumn() > 0;
}

// ── Schritt 28 rückgängig: project_cutters löschen ───────────────────────────
if (rb_table_exists($pdo, 'project_cutters')) {
    rb_step($pdo, "project_cutters: Tabelle löschen",
        "DROP TABLE project_cutters",
        $results, $dryRun, $execute);
} else {
    $results[] = ['ok' => true, 'label' => "project_cutters: Tabelle existiert nicht – übersprungen"];
}

// ── Schritt 27 rückgängig: assets löschen ────────────────────────────────────
if (rb_table_exists($pdo, 'assets')) {
    rb_step($pdo, "assets: Tabelle löschen",
        "DROP TABLE assets",
        $results, $dryRun, $execute);
} else {
    $results[] = ['ok' => true, 'label' => "assets: Tabelle existiert nicht – übersprungen"];
}

// ── Schritt 26 rückgängig: nas_folder + slug entfernen ───────────────────────
if (rb_col_exists($pdo, 'projects', 'nas_folder')) {
    rb_step($pdo, "projects.nas_folder: Spalte entfernen",
        "ALTER TABLE projects DROP COLUMN nas_folder",
        $results, $dryRun, $execute);
} else {
    $results[] = ['ok' => true, 'label' => "projects.nas_folder: Spalte existiert nicht – übersprungen"];
}

if (rb_col_exists($pdo, 'projects', 'slug')) {
    rb_step($pdo, "projects.slug: Spalte entfernen",
        "ALTER TABLE projects DROP COLUMN slug",
        $results, $dryRun, $execute);
} else {
    $results[] = ['ok' => true, 'label' => "projects.slug: Spalte existiert nicht – übersprungen"];
}

$fails = array_values(array_filter($results, fn($r) => !$r['ok']));
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NAS Rollback</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f0f0f;color:#e5e5e5;padding:24px;max-width:700px;margin:0 auto}
  h1{font-size:20px;font-weight:700;margin-bottom:4px}
  .sub{font-size:13px;color:#888;margin-bottom:20px}
  .banner{border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:14px;font-weight:500}
  .banner.ok  {background:rgba(48,209,88,.1);border:1px solid rgba(48,209,88,.3);color:#30d158}
  .banner.err {background:rgba(255,69,58,.1);border:1px solid rgba(255,69,58,.3);color:#ff453a}
  .banner.warn{background:rgba(255,165,0,.12);border:1px solid rgba(255,165,0,.3);color:#f90}
  .item{display:flex;align-items:flex-start;gap:10px;padding:8px 12px;border-radius:8px;margin-bottom:5px;font-size:13px}
  .item.ok{background:rgba(48,209,88,.06)}
  .item.err{background:rgba(255,69,58,.1)}
  .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-top:3px}
  .dot.ok{background:#30d158}.dot.err{background:#ff453a}
  .errtxt{font-size:11px;color:#ff453a;margin-top:2px}
  .btn{display:inline-block;margin-top:20px;padding:11px 22px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:700}
  .btn-danger{background:#ff453a;color:#fff}
  .btn-ghost{background:rgba(255,255,255,.08);color:#e5e5e5}
  .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
  code{background:#1c2029;padding:2px 6px;border-radius:4px;font-size:12px}
</style>
</head>
<body>
<h1>NAS-Medien-Schicht Rollback</h1>
<div class="sub">Schritte 26–28 aus run_migrations.php rückgängig machen</div>

<?php if (!$execute && !$dryRun): ?>
<div class="banner warn">
  <strong>Achtung:</strong> Dieser Rollback löscht die Tabellen <code>assets</code> und
  <code>project_cutters</code> sowie die Spalten <code>projects.nas_folder</code> und
  <code>projects.slug</code>.<br><br>
  Alle Asset-Datensätze und Cutter-Zuordnungen aus der NAS-Schicht gehen verloren.
  Bestehende Daten in anderen Tabellen bleiben unberührt.<br><br>
  Unten: Vorschau der auszuführenden Schritte. Um den Rollback wirklich durchzuführen,
  auf „Jetzt ausführen" klicken.
</div>
<?php elseif ($dryRun): ?>
<div class="banner warn">
  Dry-Run — keine Änderungen vorgenommen.
</div>
<?php elseif ($execute): ?>
<div class="banner <?= count($fails) === 0 ? 'ok' : 'err' ?>">
  <?= count($fails) === 0 ? 'Rollback erfolgreich.' : count($fails) . ' Fehler aufgetreten.' ?>
</div>
<?php endif; ?>

<?php foreach ($results as $r): ?>
<div class="item <?= $r['ok'] ? 'ok' : 'err' ?>">
  <div class="dot <?= $r['ok'] ? 'ok' : 'err' ?>"></div>
  <div>
    <div><?= htmlspecialchars($r['label']) ?></div>
    <?php if (!empty($r['sql'])): ?>
      <div style="font-size:11px;color:#888;margin-top:2px;font-family:monospace"><?= htmlspecialchars($r['sql']) ?></div>
    <?php endif; ?>
    <?php if (!empty($r['err'])): ?>
      <div class="errtxt"><?= htmlspecialchars($r['err']) ?></div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<div class="actions">
  <?php if (!$execute && !$dryRun): ?>
    <a class="btn btn-danger" href="?confirm=1">⚠ Rollback jetzt ausführen</a>
    <a class="btn btn-ghost"  href="?dry=1">Dry-Run</a>
  <?php else: ?>
    <a class="btn btn-ghost" href="./">Zurück zur App</a>
    <?php if ($dryRun): ?>
      <a class="btn btn-ghost" href="?">Vorschau ohne dry=1</a>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>
