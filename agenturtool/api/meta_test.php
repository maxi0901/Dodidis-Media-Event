<?php
declare(strict_types=1);

/**
 * Meta-Diagnose — zeigt, was PHP an Meta-Zugangsdaten sieht (Env oder
 * config.meta.php) und ob App-ID/Secret gültig sind. Analog nas_test.php.
 * Aufruf:  /agenturtool/api/meta_test.php?run=1   (nur Admin)
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/meta_env.php';
require_once __DIR__ . '/MetaClient.php';

start_app_session();
if (empty($_SESSION['uid']) || !in_array('admin', (array)($_SESSION['roles'] ?? []), true)) {
    header('Location: ../login.php');
    exit;
}

$cfg   = meta_config();
$run   = isset($_GET['run']);
$steps = [];
$ok    = static function (&$s, string $l, string $d = '') { $s[] = ['ok' => true,  'label' => $l, 'detail' => $d]; };
$fail  = static function (&$s, string $l, string $d = '') { $s[] = ['ok' => false, 'label' => $l, 'detail' => $d]; };

if ($run) {
    $mask = static fn(string $v): string =>
        $v === '' ? '(leer)' : (strlen($v) <= 6 ? '***' : substr($v, 0, 4) . '…' . substr($v, -2));

    // 1. Zugangsdaten vorhanden?
    $have = $cfg['app_id'] !== '' && $cfg['app_secret'] !== '';
    if ($have) {
        $ok($steps, 'Zugangsdaten sichtbar',
            "APP_ID={$cfg['app_id']}  SECRET={$mask($cfg['app_secret'])}  GRAPH=" . ($cfg['graph_version'] ?: 'v21.0'));
    } else {
        $miss = implode(', ', array_filter([
            $cfg['app_id'] === '' ? 'META_APP_ID' : '',
            $cfg['app_secret'] === '' ? 'META_APP_SECRET' : '',
        ]));
        $fail($steps, 'Zugangsdaten fehlen (weder Env noch config.meta.php)',
            $miss . ' — config.meta.php anlegen (siehe config.meta.php.example) oder Env prüfen.');
    }

    // 2. Redirect-URI
    if ($cfg['redirect_uri'] !== '') {
        $ok($steps, 'Redirect-URI gesetzt',
            $cfg['redirect_uri'] . '  — muss ZEICHENGENAU in der Meta-App (Facebook Login → Gültige OAuth-Redirect-URIs) hinterlegt sein.');
    } else {
        $ok($steps, 'Redirect-URI wird aus dem Request abgeleitet',
            '(kein META_REDIRECT_URI gesetzt) — ok, solange kein Reverse-Proxy Scheme/Host verfälscht.');
    }

    // 3. App-ID/Secret wirklich gültig? (App-Token via client_credentials)
    if ($have) {
        try {
            $meta = new MetaClient('x');
            $res  = $meta->get('/oauth/access_token', [
                'client_id'     => $cfg['app_id'],
                'client_secret' => $cfg['app_secret'],
                'grant_type'    => 'client_credentials',
            ]);
            if (!empty($res['access_token'])) {
                $ok($steps, 'App-Zugangsdaten gültig — Graph-API erreichbar', 'App-Token erfolgreich erhalten.');
            } else {
                $fail($steps, 'Kein App-Token erhalten', json_encode($res));
            }
        } catch (\Throwable $e) {
            $fail($steps, 'App-Zugangsdaten ungültig / Graph-Fehler', $e->getMessage());
        }
    }

    // 4. DB: bereits verbundene Konten
    try {
        $row = db_one("SELECT COUNT(*) AS c FROM social_accounts WHERE status = 'connected'");
        $ok($steps, 'Verbundene Konten in der Datenbank',
            (int)($row['c'] ?? 0) . ' Konto/Konten mit status=connected');
    } catch (\Throwable $e) {
        $fail($steps, 'DB-Abfrage social_accounts', $e->getMessage());
    }
}

$errCount = count(array_filter($steps, static fn($s) => !$s['ok']));
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<body style="font-family:system-ui;background:#0f0f0f;color:#e5e5e5;max-width:640px;margin:40px auto;padding:0 20px">
<h1 style="font-size:20px">Meta-Diagnose</h1>
<div style="color:#888;font-size:13px">Zugangsdaten-Quelle: Env zuerst, dann <code>agenturtool/config.meta.php</code>.</div>

<?php if (!$run): ?>
  <p style="margin-top:20px"><a href="?run=1" style="display:inline-block;padding:11px 22px;border-radius:10px;background:#0a84ff;color:#fff;text-decoration:none;font-weight:700">Test starten</a></p>
<?php else: ?>
  <div style="margin:16px 0;padding:12px 14px;border-radius:10px;font-weight:700;<?= $errCount ? 'background:rgba(255,60,60,.12);border:1px solid rgba(255,60,60,.35);color:#ff6b6b' : 'background:rgba(60,200,120,.12);border:1px solid rgba(60,200,120,.35);color:#48c774' ?>">
    <?= $errCount ? ($errCount . ' Fehler aufgetreten.') : 'Alle Checks bestanden — Meta ist konfiguriert.' ?>
  </div>
  <?php foreach ($steps as $s): ?>
    <div style="margin-bottom:8px;padding:11px 13px;border-radius:10px;border:1px solid <?= $s['ok'] ? 'rgba(60,200,120,.35)' : 'rgba(255,60,60,.35)' ?>;background:<?= $s['ok'] ? 'rgba(60,200,120,.07)' : 'rgba(255,60,60,.07)' ?>">
      <div style="font-weight:600;color:<?= $s['ok'] ? '#48c774' : '#ff6b6b' ?>"><?= $s['ok'] ? '●' : '●' ?> <?= htmlspecialchars($s['label']) ?></div>
      <?php if ($s['detail'] !== ''): ?><div style="color:#aaa;font-size:12px;margin-top:3px;word-break:break-word"><?= htmlspecialchars($s['detail']) ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <p style="margin-top:16px"><a href="?run=1" style="color:#0a84ff">↻ Erneut testen</a></p>
<?php endif; ?>
<p style="margin-top:8px"><a href="../index.php" style="color:#0a84ff">← Zurück zur App</a></p>
</body>
