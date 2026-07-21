<?php
declare(strict_types=1);

/**
 * Löst die Meta-/Graph-API-Zugangsdaten robust auf — in dieser Reihenfolge:
 *
 *   1. Umgebungsvariablen (getenv / $_SERVER)
 *        META_APP_ID / META_APP_SECRET / META_REDIRECT_URI / META_GRAPH_VERSION.
 *        Achtung: .htaccess-"SetEnv" wird unter PHP-FPM je nach Setup NICHT an
 *        PHP durchgereicht — dann greift automatisch Schritt 2.
 *   2. Gitignorierte PHP-Datei  agenturtool/config.meta.php
 *        return ['app_id'=>…, 'app_secret'=>…, 'redirect_uri'=>…, 'graph_version'=>…];
 *
 * So bleibt das App-Secret aus dem Git-Repo heraus und funktioniert unabhängig
 * von Apache/FPM-Env — analog zu nas_env.php / config.nas.php.
 *
 * @return array{app_id:string,app_secret:string,redirect_uri:string,graph_version:string}
 */
function meta_config(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;

    $env = static fn(string $k): string =>
        (string)(getenv($k) ?: ($_SERVER[$k] ?? '') ?: '');

    $file = [];
    $cfgFile = __DIR__ . '/../config.meta.php';
    if (is_file($cfgFile)) {
        $tmp = require $cfgFile;
        if (is_array($tmp)) $file = $tmp;
    }

    $pick = static function (string $envKey, string $fileKey) use ($env, $file): string {
        $v = $env($envKey);
        if ($v === '' && isset($file[$fileKey])) $v = (string)$file[$fileKey];
        return $v;
    };

    $cache = [
        'app_id'        => $pick('META_APP_ID', 'app_id'),
        'app_secret'    => $pick('META_APP_SECRET', 'app_secret'),
        'redirect_uri'  => $pick('META_REDIRECT_URI', 'redirect_uri'),
        'graph_version' => $pick('META_GRAPH_VERSION', 'graph_version'),
        // WhatsApp Cloud API (Kommunikation): Business-Nummer + Token + Webhook-
        // Verify-Token. Webhook-Signatur nutzt app_secret (oben).
        'wa_phone_number_id' => $pick('WA_PHONE_NUMBER_ID', 'wa_phone_number_id'),
        'wa_token'           => $pick('WA_TOKEN', 'wa_token'),
        'wa_verify_token'    => $pick('WA_VERIFY_TOKEN', 'wa_verify_token'),
        // Instagram-Automatisierung (Auto-Antworten/DMs auf Kommentare):
        // eigener Webhook-Verify-Token + eigener App-Geheimcode. Der Instagram-
        // Business-Login hat ein SEPARATES App-Secret (Meta-App -> Instagram ->
        // API-Setup -> „Instagram-App-Geheimcode"); IG-Webhooks werden damit
        // signiert, NICHT mit dem Facebook-app_secret. Fällt leer auf app_secret
        // zurück (alte Graph-API-Anbindung über die Facebook-Seite).
        'ig_verify_token'    => $pick('IG_VERIFY_TOKEN', 'ig_verify_token'),
        'ig_app_secret'      => $pick('IG_APP_SECRET', 'ig_app_secret'),
    ];
    return $cache;
}
