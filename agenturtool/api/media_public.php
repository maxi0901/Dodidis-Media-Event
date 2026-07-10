<?php
declare(strict_types=1);

/**
 * Öffentliche, SIGNIERTE Media-Auslieferung — ausschließlich für den
 * Media-Fetcher von Meta/Instagram (der nicht eingeloggt ist).
 *
 * KEIN Login. Zugriff nur mit gültiger, kurzlebiger HMAC-Signatur, die
 * content_publish_now.php erzeugt (Secret = Meta-App-Secret, server-only).
 *
 *   GET ?a=<asset_id>&exp=<unix>&sig=<hmac_sha256(a|exp, app_secret)>
 *
 * Bevorzugt die lokale Review-Kopie (media_cache/), sonst Stream vom NAS.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/nas_cache.php';
require_once __DIR__ . '/meta_env.php';
require_once __DIR__ . '/NasWebDAV.php';

$a   = (string)($_GET['a'] ?? '');
$exp = (int)($_GET['exp'] ?? 0);
$sig = (string)($_GET['sig'] ?? '');

$secret = meta_config()['app_secret'];
if ($a === '' || $exp === 0 || $sig === '' || $secret === '') { http_response_code(403); exit('Forbidden'); }
if ($exp < time())                                            { http_response_code(410); exit('Link abgelaufen'); }

$expected = hash_hmac('sha256', $a . '|' . $exp, $secret);
if (!hash_equals($expected, $sig)) { http_response_code(403); exit('Forbidden'); }

$asset = db_one(
    "SELECT id, nas_key, content_type, filename FROM assets WHERE id = ? AND status = 'stored'",
    [$a]
);
if (!$asset) { http_response_code(404); exit('Not found'); }

@set_time_limit(0);
$ct = (string)($asset['content_type'] ?: 'application/octet-stream');

// Bevorzugt lokale Review-Kopie (schnell), sonst vom NAS durchstreamen.
$cache = nas_cache_path((string)$asset['id']);
if (is_file($cache)) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: ' . $ct);
    header('Content-Length: ' . filesize($cache));
    header('Cache-Control: private, max-age=1800');
    readfile($cache);
    exit;
}

while (ob_get_level() > 0) ob_end_clean();
try {
    (new NasWebDAV())->passthru((string)$asset['nas_key'], (string)$asset['filename'], 'inline');
} catch (\Throwable $e) {
    http_response_code(502);
    exit('Media derzeit nicht verfügbar');
}
