<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/NasWebDAV.php';

start_app_session();

if (empty($_SESSION['uid']) || !in_array('admin', (array)($_SESSION['roles'] ?? []), true)) {
    header('Location: login.php');
    exit;
}

// Tests laufen nur bei explizitem POST mit gültigem CSRF-Token.
// GET zeigt nur den Bestätigungsbutton — verhindert unbeabsichtigte NAS-Operationen
// durch Cross-Site-Links oder Top-Level-Navigation.
$run = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('CSRF-Token ungültig.');
    }
    $run = true;
}
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$steps = [];

function ok(string $label, string $detail = ''): void {
    global $steps;
    $steps[] = ['ok' => true, 'label' => $label, 'detail' => $detail];
}

function fail(string $label, string $err): void {
    global $steps;
    $steps[] = ['ok' => false, 'label' => $label, 'detail' => $err];
}

if ($run):
// ── 1. Env-Vars vorhanden? ────────────────────────────────────────────────────
$base = (string)(getenv('NAS_DAV_BASE') ?: ($_SERVER['NAS_DAV_BASE'] ?? '') ?: '');
$user = (string)(getenv('NAS_DAV_USER') ?: ($_SERVER['NAS_DAV_USER'] ?? '') ?: '');
$pass = (string)(getenv('NAS_DAV_PASS') ?: ($_SERVER['NAS_DAV_PASS'] ?? '') ?: '');

if ($base && $user && $pass) {
    ok('Umgebungsvariablen gesetzt', "BASE={$base}  USER={$user}  PASS=***");
} else {
    $missing = implode(', ', array_filter([
        !$base ? 'NAS_DAV_BASE' : '',
        !$user ? 'NAS_DAV_USER' : '',
        !$pass ? 'NAS_DAV_PASS' : '',
    ]));
    fail('Umgebungsvariablen fehlen', $missing);
}

// ── 2. NasWebDAV instanziieren ────────────────────────────────────────────────
$nas = null;
try {
    $nas = new NasWebDAV();
    ok('NasWebDAV instanziiert');
} catch (\Throwable $e) {
    fail('NasWebDAV instanziieren', $e->getMessage());
}

// ── 3. Testverzeichnis anlegen (MKCOL) ───────────────────────────────────────
$testDir  = '_claude_nas_test';
$testFile = $testDir . '/ping.txt';

if ($nas) {
    try {
        $nas->ensureDir($testDir);
        ok("MKCOL /{$testDir}");
    } catch (\Throwable $e) {
        fail("MKCOL /{$testDir}", $e->getMessage());
        $nas = null; // skip remaining tests
    }
}

// ── 4. Kleine Datei hochladen (PUT) ──────────────────────────────────────────
if ($nas) {
    try {
        $content = 'NAS-Test ' . date('Y-m-d H:i:s');
        $len     = strlen($content);

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $content);
        rewind($fh);

        $ch = curl_init($nas->url($testFile));
        curl_setopt($ch, CURLOPT_USERPWD,        $user . ':' . $pass);
        curl_setopt($ch, CURLOPT_HTTPAUTH,        CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_PUT,             true);
        curl_setopt($ch, CURLOPT_INFILE,          $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE,      $len);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
        curl_setopt($ch, CURLOPT_TIMEOUT,         30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,  false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/plain',
            'Content-Length: ' . $len,
            'Expect:',
        ]);

        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if ($err) throw new \RuntimeException("cURL: {$err}");
        if ($code !== 201 && $code !== 204) throw new \RuntimeException("HTTP {$code}");

        ok("PUT /{$testFile}", "HTTP {$code} — Inhalt: \"{$content}\"");
    } catch (\Throwable $e) {
        fail("PUT /{$testFile}", $e->getMessage());
        $nas = null;
    }
}

// ── 5. HEAD — Datei auf NAS verifizieren ─────────────────────────────────────
if ($nas) {
    try {
        [$ct, $size] = $nas->head($testFile);
        if ($size > 0) {
            ok("HEAD /{$testFile}", "Content-Type: {$ct}  Size: {$size} Bytes");
        } else {
            fail("HEAD /{$testFile}", "Size=0 — Datei wurde nicht korrekt gespeichert");
        }
    } catch (\Throwable $e) {
        fail("HEAD /{$testFile}", $e->getMessage());
    }
}

// ── 6. Aufräumen (DELETE) ─────────────────────────────────────────────────────
if ($nas) {
    try {
        $nas->delete($testFile);
        ok("DELETE /{$testFile}");
    } catch (\Throwable $e) {
        fail("DELETE /{$testFile}", $e->getMessage());
    }
}

endif; // $run

$fails = array_filter($steps, fn($s) => !$s['ok']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NAS Verbindungstest</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f0f0f;color:#e5e5e5;padding:24px;max-width:700px;margin:0 auto}
  h1{font-size:20px;font-weight:700;margin-bottom:4px}
  .sub{font-size:13px;color:#888;margin-bottom:20px}
  .banner{border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:14px;font-weight:600}
  .ok  {background:rgba(48,209,88,.12);border:1px solid rgba(48,209,88,.3);color:#30d158}
  .err {background:rgba(255,69,58,.12);border:1px solid rgba(255,69,58,.3);color:#ff453a}
  .item{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-radius:8px;margin-bottom:6px;font-size:13px}
  .item.ok {background:rgba(48,209,88,.06)}
  .item.err{background:rgba(255,69,58,.10)}
  .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-top:3px}
  .dot.ok{background:#30d158}.dot.err{background:#ff453a}
  .detail{font-size:11px;color:#aaa;margin-top:3px;font-family:monospace;word-break:break-all}
  .detail.err{color:#ff6b63}
  a{color:#0a84ff;text-decoration:none;font-size:13px}
</style>
</head>
<body>
<h1>NAS Verbindungstest</h1>
<div class="sub">Prüft MKCOL → PUT → HEAD → DELETE gegen <?= htmlspecialchars(getenv('NAS_DAV_BASE') ?: '(kein BASE gesetzt)') ?></div>

<?php if (!$run): ?>
<div class="banner" style="background:rgba(255,165,0,.12);border:1px solid rgba(255,165,0,.3);color:#f90">
  Startet MKCOL, PUT, HEAD und DELETE auf dem NAS. Nur ausführen wenn der Test gewollt ist.
</div>
<form method="post">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
  <button type="submit" style="margin-top:12px;padding:11px 22px;border-radius:10px;background:#0a84ff;color:#fff;border:none;font-size:14px;font-weight:700;cursor:pointer">
    Test jetzt ausführen
  </button>
</form>
<?php else: ?>
<div class="banner <?= count($fails) === 0 ? 'ok' : 'err' ?>">
  <?= count($fails) === 0 ? 'Alle Schritte erfolgreich — NAS-Verbindung funktioniert.' : count($fails) . ' Fehler aufgetreten.' ?>
</div>
<?php foreach ($steps as $s): ?>
<div class="item <?= $s['ok'] ? 'ok' : 'err' ?>">
  <div class="dot <?= $s['ok'] ? 'ok' : 'err' ?>"></div>
  <div>
    <div><?= htmlspecialchars($s['label']) ?></div>
    <?php if ($s['detail']): ?>
      <div class="detail <?= $s['ok'] ? '' : 'err' ?>"><?= htmlspecialchars($s['detail']) ?></div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<p style="margin-top:20px"><a href="./">← Zurück zur App</a></p>
</body>
</html>
