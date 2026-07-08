<?php
declare(strict_types=1);

/**
 * Löst die NAS-WebDAV-Zugangsdaten robust auf — in dieser Reihenfolge:
 *
 *   1. Umgebungsvariablen (getenv / $_SERVER)
 *        z. B. .htaccess "SetEnv" oder PHP-FPM-Pool "env[…]".
 *        Achtung: .htaccess-SetEnv wird unter PHP-FPM je nach Setup NICHT an
 *        PHP durchgereicht — dann greift automatisch Schritt 2.
 *   2. Gitignorierte PHP-Datei  agenturtool/config.nas.php
 *        Unabhängig von Apache/FPM-Env, überlebt Handler-Wechsel/Updates.
 *        Muss ein Array zurückgeben:
 *           return ['base' => 'https://…host…/pfad', 'user' => 'toolsvc', 'pass' => '…'];
 *
 * So ändert sich bei einem neuen Tunnel-Host nur EINE Zeile in config.nas.php,
 * und das NAS-Passwort bleibt aus dem Git-Repo heraus.
 *
 * @return array{base:string,user:string,pass:string}
 */
function nas_credentials(): array
{
    // 1. Env / SetEnv
    $base = (string)(getenv('NAS_DAV_BASE') ?: ($_SERVER['NAS_DAV_BASE'] ?? '') ?: '');
    $user = (string)(getenv('NAS_DAV_USER') ?: ($_SERVER['NAS_DAV_USER'] ?? '') ?: '');
    $pass = (string)(getenv('NAS_DAV_PASS') ?: ($_SERVER['NAS_DAV_PASS'] ?? '') ?: '');

    // 2. Fallback: config.nas.php (nur für die noch fehlenden Werte)
    if ($base === '' || $user === '' || $pass === '') {
        $cfgFile = __DIR__ . '/../config.nas.php';
        if (is_file($cfgFile)) {
            $nas = require $cfgFile;
            if (is_array($nas)) {
                if ($base === '') $base = (string)($nas['base'] ?? '');
                if ($user === '') $user = (string)($nas['user'] ?? '');
                if ($pass === '') $pass = (string)($nas['pass'] ?? '');
            }
        }
    }

    return ['base' => $base, 'user' => $user, 'pass' => $pass];
}
