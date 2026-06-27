<?php
declare(strict_types=1);

/**
 * Diagnose-Log für das Kalender-Scrollen.
 * - POST: hängt eine (eingeloggte Staff-)Diagnosezeile an private_uploads/cal-debug.log an.
 *         Kein CSRF nötig (append-only, harmlos) – sendBeacon/fetch kann keine Header setzen.
 * - GET (nur Admin/Manager): zeigt das Log als text/plain; mit ?clear=1 wird es geleert.
 * Die Datei liegt im privaten Upload-Ordner (per .htaccess nicht öffentlich erreichbar);
 * Ansehen geht über diesen Endpoint oder per FTP/Plesk.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$cfg     = require __DIR__ . '/../config.php';
$file    = $cfg['private_path'] . '/cal-debug.log';
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if ($session['type'] === 'customer' || !has_role('admin', 'manager')) {
        json_err(403, 'Keine Berechtigung.');
    }
    if (isset($_GET['clear'])) {
        @file_put_contents($file, '');
        json_ok(['cleared' => true]);
    }
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    if (is_readable($file)) {
        readfile($file);
    } else {
        echo "(noch keine Diagnosedaten)\n";
    }
    exit;
}

if ($method === 'POST') {
    if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');

    // sicherstellen, dass der Zielordner existiert
    $dir = dirname($file);
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }

    $raw = file_get_contents('php://input');
    if ($raw === false) $raw = '';
    $raw = substr($raw, 0, 2000);                          // pro Zeile begrenzen
    $raw = str_replace(["\r", "\n"], [' ', ' '], $raw);    // alles in eine Zeile

    // Rotation: bei > 2 MB zurücksetzen, damit die Datei nicht unbegrenzt wächst
    if (is_file($file) && filesize($file) > 2 * 1024 * 1024) {
        @file_put_contents($file, '');
    }

    $ts   = date('Y-m-d H:i:s');
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '?';
    $name = $session['name'] ?? ($session['uid'] ?? '?');
    @file_put_contents($file, "[$ts] {$name} {$ip}  {$raw}\n", FILE_APPEND | LOCK_EX);

    http_response_code(204);
    exit;
}

json_err(405, 'Methode nicht erlaubt.');
