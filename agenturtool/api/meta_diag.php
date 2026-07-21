<?php
declare(strict_types=1);

/**
 * Admin-Diagnose für die Meta-Anbindung (WhatsApp + Instagram-Automatisierung).
 * Zeigt OHNE Server-Log, ob die Konfiguration stimmt und was die letzten
 * Webhook-Zustellungen gemacht haben. Gibt NIE Secrets im Klartext aus.
 *
 *   GET (nur Admin)  → Status-JSON
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/meta_env.php';

$session = require_login();
if (($session['type'] ?? '') !== 'staff' || !has_role('admin')) {
    json_err(403, 'Nur Admins.');
}

$cfg = meta_config();

$env = static fn(string $k): string => (string)(getenv($k) ?: ($_SERVER[$k] ?? '') ?: '');

$cfgFile    = __DIR__ . '/../config.meta.php';
$fileExists = is_file($cfgFile);
$file       = [];
if ($fileExists) {
    $tmp = @require $cfgFile;
    if (is_array($tmp)) $file = $tmp;
}

/** Beschreibt einen Secret-/Token-Wert, ohne ihn preiszugeben. */
$describe = static function (string $s): array {
    $len = strlen($s);
    return [
        'set'           => $len > 0,
        'len'           => $len,
        'preview'       => $len >= 6 ? (substr($s, 0, 3) . '…' . substr($s, -3)) : ($len ? '***' : ''),
        'placeholder'   => ($len > 0 && stripos($s, 'HIER') !== false),
        'hasWhitespace' => ($s !== trim($s)),
    ];
};

$out = [
    'appId'            => $cfg['app_id'],
    'graphVersion'     => $cfg['graph_version'],
    'configFileExists' => $fileExists,
    'appSecret'        => array_merge($describe((string)$cfg['app_secret']), [
        'source'      => $env('META_APP_SECRET') !== '' ? 'Umgebung (SetEnv)' : (($file['app_secret'] ?? '') !== '' ? 'config.meta.php' : 'keine'),
        'envOverride' => $env('META_APP_SECRET') !== '',
    ]),
    'igAppSecret'      => array_merge($describe((string)$cfg['ig_app_secret']), [
        'usedForWebhook' => ((string)$cfg['ig_app_secret'] !== '')
            ? 'Instagram-App-Geheimcode'
            : 'Facebook-App-Geheimcode (Fallback)',
    ]),
    'igVerifyToken'    => $describe((string)$cfg['ig_verify_token']),
    'waVerifyToken'    => $describe((string)$cfg['wa_verify_token']),
    'waPhoneNumberId'  => ['set' => (string)$cfg['wa_phone_number_id'] !== ''],
    'waToken'          => $describe((string)$cfg['wa_token']),
    'lastWebhooks'     => [],
];

// Letzte Webhook-Versuche (von ig_webhook.php / wa_webhook.php in app_config abgelegt)
try {
    foreach (['ig_webhook_last' => 'Instagram', 'wa_webhook_last' => 'WhatsApp'] as $key => $label) {
        $row = db_one("SELECT value, updated_at AS updatedAt FROM app_config WHERE `key` = ?", [$key]);
        if (!$row) continue;
        $v = json_decode((string)$row['value'], true);
        if (is_array($v)) {
            $v['channel']   = $label;
            $v['updatedAt'] = $row['updatedAt'];
            $out['lastWebhooks'][] = $v;
        }
    }
} catch (\Throwable $e) { /* app_config evtl. nicht vorhanden */ }

json_ok($out);
