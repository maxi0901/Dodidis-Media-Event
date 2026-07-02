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

function ok(string $label, string $detail = '', bool $pre = false): void {
    global $steps;
    $steps[] = ['ok' => true,  'label' => $label, 'detail' => $detail, 'pre' => $pre];
}
function fail(string $label, string $detail = '', bool $pre = false): void {
    global $steps;
    $steps[] = ['ok' => false, 'label' => $label, 'detail' => $detail, 'pre' => $pre];
}
function info(string $label, string $detail = '', bool $pre = false): void {
    global $steps;
    $steps[] = ['ok' => null,  'label' => $label, 'detail' => $detail, 'pre' => $pre];
}

if ($run):

// ── 1. Env-Vars ───────────────────────────────────────────────────────────────
$base = (string)(getenv('NAS_DAV_BASE') ?: ($_SERVER['NAS_DAV_BASE'] ?? '') ?: '');
$user = (string)(getenv('NAS_DAV_USER') ?: ($_SERVER['NAS_DAV_USER'] ?? '') ?: '');
$pass = (string)(getenv('NAS_DAV_PASS') ?: ($_SERVER['NAS_DAV_PASS'] ?? '') ?: '');

if ($base && $user && $pass) {
    ok('Env-Vars gesetzt', "BASE={$base}  USER={$user}  PASS=***");
} else {
    $missing = implode(', ', array_filter([
        !$base ? 'NAS_DAV_BASE' : '',
        !$user ? 'NAS_DAV_USER' : '',
        !$pass ? 'NAS_DAV_PASS' : '',
    ]));
    fail('Env-Vars fehlen', $missing);
}

// ── 2. PHP / cURL Umgebung ────────────────────────────────────────────────────
$cv         = curl_version();
$hasIPv6    = (bool)(($cv['features'] ?? 0) & CURL_VERSION_IPV6);
$hasSSL     = (bool)(($cv['features'] ?? 0) & CURL_VERSION_SSL);
$curlLine   = 'PHP ' . PHP_VERSION
    . ' | cURL ' . ($cv['version'] ?? '?')
    . ' | IPv6-Support: ' . ($hasIPv6 ? 'JA' : 'NEIN ← Problem!')
    . ' | SSL: '  . ($hasSSL  ? 'JA' : 'NEIN')
    . ($hasSSL ? ' (' . ($cv['ssl_version'] ?? '') . ')' : '');
$hasIPv6 ? ok('PHP / cURL', $curlLine) : fail('PHP / cURL', $curlLine);

// ── 3. DNS-Auflösung & Host-Analyse ──────────────────────────────────────────
$parsed   = $base ? parse_url($base) : [];
$rawHost  = (string)($parsed['host'] ?? '');
$scheme   = strtolower((string)($parsed['scheme'] ?? 'http'));
$urlPort  = (int)($parsed['port'] ?? ($scheme === 'https' ? 443 : 5005));

// Literal-IPv6 in eckigen Klammern: [::1] → ::1
$isLiteralIPv6 = (strlen($rawHost) > 0 && $rawHost[0] === '[');
$host          = $isLiteralIPv6 ? trim($rawHost, '[]') : $rawHost;

info('Host aus URL', "{$host}  Port: {$urlPort}  " . ($isLiteralIPv6 ? '(direkte IPv6-Adresse)' : '(Hostname)'));

$resolvedIPs = [];

if ($isLiteralIPv6) {
    info('DNS-Auflösung', 'Kein DNS nötig — direkte IPv6-Literal-Adresse');
    $resolvedIPs[] = ['addr' => $host, 'type' => 'AAAA'];
} elseif ($host !== '') {
    $ipv4List = @gethostbynamel($host) ?: [];
    $aaaa     = @dns_get_record($host, DNS_AAAA) ?: [];
    $ipv6List = array_column($aaaa, 'ipv6');

    foreach ($ipv4List as $ip) { $resolvedIPs[] = ['addr' => $ip,  'type' => 'A'];    }
    foreach ($ipv6List as $ip) { $resolvedIPs[] = ['addr' => $ip,  'type' => 'AAAA']; }

    if ($resolvedIPs) {
        $lines = array_map(fn($r) => $r['type'] . ': ' . $r['addr'], $resolvedIPs);
        ok('DNS-Auflösung', implode('  |  ', $lines));
    } else {
        fail('DNS-Auflösung', "Keine Adressen für {$host} — DNS-Fehler oder Hostname falsch");
    }
}

// ── 4. TCP-Verbindungstest pro aufgelöster Adresse ───────────────────────────
foreach ($resolvedIPs as $rec) {
    $addr   = $rec['addr'];
    $type   = $rec['type'];
    $target = ($type === 'AAAA') ? "[{$addr}]" : $addr;
    $errno  = 0;
    $errstr = '';
    $t0     = microtime(true);
    $sock   = @fsockopen('tcp://' . $target, $urlPort, $errno, $errstr, 5.0);
    $ms     = (int)round((microtime(true) - $t0) * 1000);

    if ($sock) {
        fclose($sock);
        ok("TCP {$type} :{$urlPort}", "{$addr} — Verbunden in {$ms} ms");
    } else {
        // errno 111 = Connection refused, 110 = Timed out, 101 = Network unreachable
        $hint = match($errno) {
            111 => ' → Port erreichbar, aber Dienst lehnt ab (WebDAV läuft nicht auf diesem Port?)',
            110 => ' → Timeout — Firewall blockiert oder NAS antwortet nicht',
            101 => ' → Netzwerk nicht erreichbar — kein IPv6-Route oder DS-Lite-Problem',
            113 => ' → No route to host',
            default => '',
        };
        fail("TCP {$type} :{$urlPort}", "{$addr} — {$errstr} (errno {$errno}, {$ms} ms){$hint}");
    }
}

// ── 5. cURL MKCOL mit Verbose-Log ────────────────────────────────────────────
if ($base) {
    $testUrl      = rtrim($base, '/') . '/_claude_nas_diag';
    $verboseStream = fopen('php://temp', 'r+');

    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_USERPWD,        $user . ':' . $pass);
    curl_setopt($ch, CURLOPT_HTTPAUTH,        CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,   'MKCOL');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,  true);
    curl_setopt($ch, CURLOPT_TIMEOUT,         15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,  false);
    curl_setopt($ch, CURLOPT_VERBOSE,         true);
    curl_setopt($ch, CURLOPT_STDERR,          $verboseStream);

    $t0    = microtime(true);
    curl_exec($ch);
    $ms    = (int)round((microtime(true) - $t0) * 1000);
    $code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cErr  = curl_error($ch);
    $cNo   = curl_errno($ch);
    $cIP   = (string)(curl_getinfo($ch, CURLINFO_PRIMARY_IP)   ?: '—');
    $cPort = (int)(curl_getinfo($ch, CURLINFO_PRIMARY_PORT) ?? 0);
    curl_close($ch);

    rewind($verboseStream);
    $verboseRaw = stream_get_contents($verboseStream);
    fclose($verboseStream);

    // Passwort aus Verbose-Log entfernen
    $verboseSafe = preg_replace(
        '/Authorization: Basic [^\r\n]+/',
        'Authorization: Basic ***',
        $verboseRaw ?? ''
    );

    if ($cErr) {
        fail("MKCOL cURL (errno {$cNo})", "Fehler: {$cErr} | Ziel-IP: {$cIP}:{$cPort} | {$ms} ms");
    } elseif ($code === 201 || $code === 405) {
        ok("MKCOL HTTP {$code}", "Ziel-IP: {$cIP}:{$cPort} | {$ms} ms");
        // Aufräumen
        $del = curl_init($testUrl);
        curl_setopt($del, CURLOPT_USERPWD,      $user . ':' . $pass);
        curl_setopt($del, CURLOPT_HTTPAUTH,      CURLAUTH_BASIC);
        curl_setopt($del, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($del, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($del, CURLOPT_TIMEOUT,       10);
        curl_exec($del);
        curl_close($del);
    } else {
        fail("MKCOL HTTP {$code}", "Ziel-IP: {$cIP}:{$cPort} | {$ms} ms");
    }

    if ($verboseSafe) {
        info('cURL Verbose-Log', htmlspecialchars($verboseSafe), true);
    }
}

// ── 6. Volltest (nur wenn TCP-Verbindung oben geklappt hat) ──────────────────
$tcpOk = count(array_filter($steps, fn($s) => $s['ok'] === true && str_starts_with($s['label'], 'TCP'))) > 0;

if ($tcpOk && $base && $user && $pass) {
    $nas      = null;
    $testFile = '_claude_nas_diag/ping.txt';
    try {
        $nas = new NasWebDAV();
        ok('NasWebDAV instanziiert');
    } catch (\Throwable $e) {
        fail('NasWebDAV instanziieren', $e->getMessage());
    }

    if ($nas) {
        try {
            $nas->ensureDir('_claude_nas_diag');
            ok('MKCOL /_claude_nas_diag');
        } catch (\Throwable $e) {
            fail('MKCOL /_claude_nas_diag', $e->getMessage());
            $nas = null;
        }
    }

    if ($nas) {
        try {
            $content = 'NAS-Test ' . date('Y-m-d H:i:s');
            $len     = strlen($content);
            $fh      = fopen('php://temp', 'r+');
            fwrite($fh, $content);
            rewind($fh);

            $ch = curl_init($nas->url($testFile));
            curl_setopt($ch, CURLOPT_USERPWD,       $user . ':' . $pass);
            curl_setopt($ch, CURLOPT_HTTPAUTH,       CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PUT,            true);
            curl_setopt($ch, CURLOPT_INFILE,         $fh);
            curl_setopt($ch, CURLOPT_INFILESIZE,     $len);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT,        30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain', "Content-Length: {$len}", 'Expect:']);
            curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            fclose($fh);

            if ($err)  throw new \RuntimeException("cURL: {$err}");
            if ($code !== 201 && $code !== 204) throw new \RuntimeException("HTTP {$code}");
            ok("PUT /{$testFile}", "HTTP {$code}");
        } catch (\Throwable $e) {
            fail("PUT /{$testFile}", $e->getMessage());
            $nas = null;
        }
    }

    if ($nas) {
        try {
            [$ct, $size] = $nas->head($testFile);
            $size > 0
                ? ok("HEAD /{$testFile}", "Content-Type: {$ct}  Size: {$size} Bytes")
                : fail("HEAD /{$testFile}", 'Size=0');
        } catch (\Throwable $e) {
            fail("HEAD /{$testFile}", $e->getMessage());
        }
        try {
            $nas->delete($testFile);
            ok("DELETE /{$testFile}");
        } catch (\Throwable $e) {
            fail("DELETE /{$testFile}", $e->getMessage());
        }
    }
}

endif; // $run

$fails = array_filter($steps, fn($s) => $s['ok'] === false);
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>NAS Diagnose</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#0f0f0f;color:#e5e5e5;padding:24px;max-width:800px;margin:0 auto}
  h1{font-size:20px;font-weight:700;margin-bottom:4px}
  .sub{font-size:13px;color:#888;margin-bottom:20px}
  .banner{border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:14px;font-weight:600}
  .b-ok  {background:rgba(48,209,88,.12);border:1px solid rgba(48,209,88,.3);color:#30d158}
  .b-err {background:rgba(255,69,58,.12);border:1px solid rgba(255,69,58,.3);color:#ff453a}
  .item{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-radius:8px;margin-bottom:6px;font-size:13px}
  .item-ok  {background:rgba(48,209,88,.06)}
  .item-err {background:rgba(255,69,58,.10)}
  .item-info{background:rgba(255,255,255,.04)}
  .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;margin-top:3px}
  .dot-ok{background:#30d158}.dot-err{background:#ff453a}.dot-info{background:#636366}
  .detail{font-size:11px;color:#aaa;margin-top:3px;font-family:monospace;word-break:break-all}
  .detail-err{color:#ff6b63}
  pre.detail{white-space:pre-wrap;line-height:1.5;max-height:300px;overflow-y:auto;
             background:#1a1a1a;border-radius:6px;padding:8px;border:1px solid #333}
  a{color:#0a84ff;text-decoration:none;font-size:13px}
  .section{font-size:11px;font-weight:700;color:#555;text-transform:uppercase;
           letter-spacing:.08em;margin:16px 0 6px}
</style>
</head>
<body>
<h1>NAS Diagnose</h1>
<div class="sub">DNS → TCP → cURL Verbose → MKCOL/PUT/HEAD/DELETE gegen <?= htmlspecialchars($base ?? (getenv('NAS_DAV_BASE') ?: '(kein BASE gesetzt)')) ?></div>

<?php if (!$run): ?>
<div class="banner" style="background:rgba(255,165,0,.12);border:1px solid rgba(255,165,0,.3);color:#f90">
  Führt DNS-Auflösung, TCP-Test und WebDAV-Operationen durch. Nur ausführen wenn gewollt.
</div>
<form method="post">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
  <button type="submit" style="margin-top:12px;padding:11px 22px;border-radius:10px;background:#0a84ff;
    color:#fff;border:none;font-size:14px;font-weight:700;cursor:pointer">
    Diagnose starten
  </button>
</form>
<?php else: ?>
<div class="banner <?= count($fails) === 0 ? 'b-ok' : 'b-err' ?>">
  <?= count($fails) === 0
    ? 'Alle Schritte erfolgreich — NAS-Verbindung funktioniert.'
    : count($fails) . ' Fehler aufgetreten.' ?>
</div>

<?php foreach ($steps as $s):
    $okVal   = $s['ok'];
    $itemCls = $okVal === true ? 'item-ok' : ($okVal === false ? 'item-err' : 'item-info');
    $dotCls  = $okVal === true ? 'dot-ok'  : ($okVal === false ? 'dot-err'  : 'dot-info');
    $detCls  = $okVal === false ? 'detail-err' : '';
?>
<div class="item <?= $itemCls ?>">
  <div class="dot <?= $dotCls ?>" style="margin-top:4px"></div>
  <div style="width:100%">
    <div><?= htmlspecialchars($s['label']) ?></div>
    <?php if ($s['detail']): ?>
      <?php if (!empty($s['pre'])): ?>
        <pre class="detail <?= $detCls ?>"><?= $s['detail'] ?></pre>
      <?php else: ?>
        <div class="detail <?= $detCls ?>"><?= htmlspecialchars($s['detail']) ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<p style="margin-top:20px"><a href="./">← Zurück zur App</a></p>
</body>
</html>
