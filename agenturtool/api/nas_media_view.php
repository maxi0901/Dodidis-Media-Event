<?php
declare(strict_types=1);

/**
 * Inline-Wiedergabe eines Assets für den Review-Player.
 *
 * Bevorzugt die lokale Review-Kopie (media_cache/) mit HTTP-Range-Support —
 * damit funktioniert Spulen im <video>-Element. Fehlt die Kopie (z. B. Asset
 * vor Stufe 2 hochgeladen oder Cache bereinigt), wird direkt vom NAS
 * durchgestreamt (abspielbar, aber ohne Seek).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/NasWebDAV.php';
require_once __DIR__ . '/nas_cache.php';

$session = require_login();
if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen Medien ansehen.');
}

$id     = $_GET['id'] ?? null;
$projId = $_GET['project_id'] ?? null;
if (!$id) json_err(400, 'id fehlt.');

@set_time_limit(0);

// Manuell abgelegte NAS-Datei (kein DB-Eintrag): direkt vom NAS inline streamen.
if (str_starts_with((string)$id, 'nas:')) {
    if (!$projId) json_err(400, 'project_id fehlt.');
    require_once __DIR__ . '/nas_files.php'; // stellt nas_resolve_manual bereit
    [$key, $fname, ] = nas_resolve_manual((string)$id, (string)$projId, $session);
    while (ob_get_level() > 0) ob_end_clean();
    try {
        (new NasWebDAV())->passthru($key, $fname, 'inline');
    } catch (\Throwable $e) {
        if (!headers_sent()) { http_response_code(502); echo 'NAS-Fehler'; }
    }
    exit;
}

$a = db_one("SELECT * FROM assets WHERE id = ?", [$id]);
if (!$a) json_err(404, 'Asset nicht gefunden.');
requireProjectAccess((string)$a['project_id'], $session);

if ($a['status'] !== 'stored') {
    json_err(409, 'Asset ist noch nicht vollständig gespeichert.');
}

$ctype    = (string)($a['content_type'] ?: 'application/octet-stream');
$filename = (string)$a['filename'];
$cache    = nas_cache_path((string)$id);

@set_time_limit(0);
while (ob_get_level() > 0) ob_end_clean();

// ── Lokale Review-Kopie: mit Range ausliefern ────────────────────────────────
if (is_file($cache)) {
    serve_file_with_range($cache, $ctype, $filename);
    exit;
}

// ── Fallback: direkt vom NAS streamen (inline, ohne Seek) ────────────────────
try {
    $nas = new NasWebDAV();
    $nas->passthru((string)$a['nas_key'], $filename, 'inline');
} catch (\Throwable $e) {
    if (!headers_sent()) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'NAS nicht erreichbar: ' . $e->getMessage()]);
    }
}
exit;

// ─────────────────────────────────────────────────────────────────────────────

function serve_file_with_range(string $path, string $ctype, string $filename): void
{
    $size  = filesize($path);
    $start = 0;
    $end   = $size - 1;

    header('Accept-Ranges: bytes');

    $range = $_SERVER['HTTP_RANGE'] ?? '';
    if ($range !== '' && preg_match('/bytes=(\d*)-(\d*)/', $range, $m)) {
        if ($m[1] === '' && $m[2] !== '') {
            // Suffix-Range: letzte N Bytes
            $start = max(0, $size - (int)$m[2]);
        } else {
            if ($m[1] !== '') $start = (int)$m[1];
            if ($m[2] !== '') $end   = min((int)$m[2], $size - 1);
        }
        if ($start > $end || $start >= $size) {
            http_response_code(416);
            header("Content-Range: bytes */{$size}");
            exit;
        }
        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$size}");
    }

    header('Content-Type: ' . $ctype);
    header('Content-Length: ' . ($end - $start + 1));
    header('Content-Disposition: inline; filename="' . str_replace('"', '\\"', $filename) . '"');
    header('Cache-Control: private, max-age=3600');
    header('X-Accel-Buffering: no');

    $fh = fopen($path, 'rb');
    if (!$fh) { http_response_code(500); exit; }
    fseek($fh, $start);
    $remaining = $end - $start + 1;
    while ($remaining > 0 && !feof($fh)) {
        $chunk = fread($fh, (int)min(131072, $remaining));
        if ($chunk === false) break;
        echo $chunk;
        $remaining -= strlen($chunk);
        flush();
    }
    fclose($fh);
}
