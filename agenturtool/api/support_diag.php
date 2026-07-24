<?php
declare(strict_types=1);

/**
 * System-/PHP-Analyse für die interne Support-Rolle.
 * Zeigt PHP-Umgebung, DB-Verbindung/Tabellen, Config-Dateien, Limits, den
 * letzten Teil des PHP-Fehler-Logs sowie die zuletzt fehlgeschlagenen
 * API-Requests (Ring-Puffer aus app_config). Nur für Support sichtbar.
 *
 *   GET (nur Support) → Status-JSON
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if (($session['type'] ?? '') !== 'staff' || !has_role('support')) {
    json_err(403, 'Nur für Supportmitarbeiter.');
}

// ── PHP-Umgebung ─────────────────────────────────────────────────────────────
$exts = ['curl','pdo_mysql','mbstring','fileinfo','openssl','gd','zip','json','intl'];
$extStatus = [];
foreach ($exts as $e) $extStatus[$e] = extension_loaded($e);

$php = [
    'version'   => PHP_VERSION,
    'sapi'      => PHP_SAPI,
    'extensions'=> $extStatus,
    'limits'    => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size'       => ini_get('post_max_size'),
        'memory_limit'        => ini_get('memory_limit'),
        'max_execution_time'  => ini_get('max_execution_time'),
        'max_file_uploads'    => ini_get('max_file_uploads'),
        'display_errors'      => ini_get('display_errors'),
    ],
];

// ── Datenbank ────────────────────────────────────────────────────────────────
$db = ['connected' => false, 'serverVersion' => null, 'tables' => []];
try {
    $pdo = db();
    $db['connected'] = true;
    try { $db['serverVersion'] = (string)$pdo->getAttribute(\PDO::ATTR_SERVER_VERSION); } catch (\Throwable $e) {}
    $tables = ['users','user_roles','projects','customers','assets','asset_comments',
               'content_queue','shoot_days','social_accounts','ig_rules','ig_events',
               'app_config','contracts','vacations','todos'];
    foreach ($tables as $t) {
        $entry = ['exists' => false, 'rows' => null];
        try {
            $r = db_one("SELECT COUNT(*) AS n FROM `{$t}`");
            $entry['exists'] = true;
            $entry['rows']   = (int)($r['n'] ?? 0);
        } catch (\Throwable $e) {
            $entry['exists'] = false;
        }
        $db['tables'][$t] = $entry;
    }
} catch (\Throwable $e) {
    $db['error'] = $e->getMessage();
}

// ── Config-Dateien (nur Vorhandensein, KEINE Inhalte/Secrets) ─────────────────
$root = dirname(__DIR__);
$config = [
    'config.php'      => is_file($root . '/config.php'),
    'config.meta.php' => is_file($root . '/config.meta.php'),
    'config.nas.php'  => is_file($root . '/config.nas.php'),
];

// ── Speicherplatz (App-Verzeichnis) ──────────────────────────────────────────
$disk = ['error' => null];
try {
    $free  = @disk_free_space($root);
    $total = @disk_total_space($root);
    $disk['freeBytes']  = $free  !== false ? (int)$free  : null;
    $disk['totalBytes'] = $total !== false ? (int)$total : null;
    if ($free !== false && $total !== false && $total > 0) {
        $disk['usedPct'] = round(($total - $free) / $total * 100, 1);
    }
} catch (\Throwable $e) { $disk['error'] = $e->getMessage(); }

// ── PHP-Fehler-Log (best effort — auf Shared-Hosting evtl. nicht lesbar) ──────
$log = ['path' => (string)ini_get('error_log'), 'readable' => false, 'tail' => null];
try {
    $p = $log['path'];
    if ($p && $p !== 'syslog' && is_file($p) && is_readable($p)) {
        $log['readable'] = true;
        $size = filesize($p);
        $max  = 16000; // letzte ~16 KB reichen
        $fh = fopen($p, 'rb');
        if ($fh) {
            if ($size > $max) fseek($fh, -$max, SEEK_END);
            $tail = stream_get_contents($fh);
            fclose($fh);
            $log['tail'] = $tail !== false ? $tail : null;
            $log['truncated'] = $size > $max;
        }
    }
} catch (\Throwable $e) { $log['error'] = $e->getMessage(); }

// ── Letzte fehlgeschlagene API-Requests (Ring-Puffer) ─────────────────────────
$serverErrors = [];
try {
    $row = db_one("SELECT value FROM app_config WHERE `key` = 'support_errors'");
    if ($row && !empty($row['value'])) {
        $d = json_decode((string)$row['value'], true);
        if (is_array($d)) $serverErrors = array_reverse($d); // neueste zuerst
    }
} catch (\Throwable $e) { /* app_config evtl. nicht vorhanden */ }

json_ok([
    'time'         => date('Y-m-d H:i:s'),
    'php'          => $php,
    'db'           => $db,
    'config'       => $config,
    'disk'         => $disk,
    'phpLog'       => $log,
    'serverErrors' => $serverErrors,
]);
