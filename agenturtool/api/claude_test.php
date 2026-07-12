<?php
declare(strict_types=1);

/**
 * Claude-Diagnose — zeigt, was PHP an API-Zugangsdaten sieht (Env oder
 * config.claude.php) und ob der Key gültig ist (echter Mini-Aufruf). Analog
 * meta_test.php / nas_test.php.
 * Aufruf:  /agenturtool/api/claude_test.php?run=1   (nur Admin)
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/claude_env.php';
require_once __DIR__ . '/ClaudeClient.php';

start_app_session();
if (empty($_SESSION['uid']) || !in_array('admin', (array)($_SESSION['roles'] ?? []), true)) {
    header('Location: ../login.php');
    exit;
}

$cfg   = claude_config();
$run   = isset($_GET['run']);
$steps = [];
$ok    = static function (&$s, string $l, string $d = '') { $s[] = ['ok' => true,  'label' => $l, 'detail' => $d]; };
$fail  = static function (&$s, string $l, string $d = '') { $s[] = ['ok' => false, 'label' => $l, 'detail' => $d]; };

if ($run) {
    $mask = static fn(string $v): string =>
        $v === '' ? '(leer)' : (strlen($v) <= 10 ? '***' : substr($v, 0, 8) . '…' . substr($v, -4));

    // 1. API-Key vorhanden?
    if ($cfg['api_key'] !== '') {
        $ok($steps, 'API-Key sichtbar',
            "KEY={$mask($cfg['api_key'])}  MODELL={$cfg['model']}  MAX_TOKENS={$cfg['max_tokens']}");
    } else {
        $fail($steps, 'API-Key fehlt (weder Env noch config.claude.php)',
            'ANTHROPIC_API_KEY setzen oder config.claude.php anlegen (siehe config.claude.php.example). '
            . 'Hinweis: Claude-Max-Abo ist KEIN API-Key — Key aus console.anthropic.com holen.');
    }

    // 2. Key wirklich gültig? Winziger echter Aufruf (bewusst 16 Tokens).
    if ($cfg['api_key'] !== '') {
        try {
            $reply = (new ClaudeClient())->complete(
                'Antworte mit genau einem Wort.',
                'Sag "bereit".',
                16
            );
            if ($reply !== '') {
                $ok($steps, 'API-Key gültig — Claude erreichbar', 'Antwort: ' . mb_substr($reply, 0, 60));
            } else {
                $fail($steps, 'Leere Antwort erhalten', 'Key evtl. ok, aber keine Text-Antwort.');
            }
        } catch (\Throwable $e) {
            $fail($steps, 'API-Key ungültig / Claude-Fehler', $e->getMessage());
        }
    }
}

$errCount = count(array_filter($steps, static fn($s) => !$s['ok']));
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<body style="font-family:system-ui;background:#0f0f0f;color:#e5e5e5;max-width:640px;margin:40px auto;padding:0 20px">
<h1 style="font-size:20px">Claude-Diagnose</h1>
<div style="color:#888;font-size:13px">Zugangsdaten-Quelle: Env zuerst, dann <code>agenturtool/config.claude.php</code>. Der Test macht einen echten (winzigen) API-Aufruf.</div>

<?php if (!$run): ?>
  <p style="margin-top:20px"><a href="?run=1" style="display:inline-block;padding:11px 22px;border-radius:10px;background:#0a84ff;color:#fff;text-decoration:none;font-weight:700">Test starten</a></p>
<?php else: ?>
  <div style="margin:16px 0;padding:12px 14px;border-radius:10px;font-weight:700;<?= $errCount ? 'background:rgba(255,60,60,.12);border:1px solid rgba(255,60,60,.35);color:#ff6b6b' : 'background:rgba(60,200,120,.12);border:1px solid rgba(60,200,120,.35);color:#48c774' ?>">
    <?= $errCount ? ($errCount . ' Fehler aufgetreten.') : 'Alle Checks bestanden — Claude ist angebunden.' ?>
  </div>
  <?php foreach ($steps as $s): ?>
    <div style="margin-bottom:8px;padding:11px 13px;border-radius:10px;border:1px solid <?= $s['ok'] ? 'rgba(60,200,120,.35)' : 'rgba(255,60,60,.35)' ?>;background:<?= $s['ok'] ? 'rgba(60,200,120,.07)' : 'rgba(255,60,60,.07)' ?>">
      <div style="font-weight:600;color:<?= $s['ok'] ? '#48c774' : '#ff6b6b' ?>">● <?= htmlspecialchars($s['label']) ?></div>
      <?php if ($s['detail'] !== ''): ?><div style="color:#aaa;font-size:12px;margin-top:3px;word-break:break-word"><?= htmlspecialchars($s['detail']) ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
  <p style="margin-top:16px"><a href="?run=1" style="color:#0a84ff">↻ Erneut testen</a></p>
<?php endif; ?>
<p style="margin-top:8px"><a href="../index.php" style="color:#0a84ff">← Zurück zur App</a></p>
</body>
