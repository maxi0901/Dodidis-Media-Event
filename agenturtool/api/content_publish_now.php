<?php
declare(strict_types=1);

/**
 * Direktes Veröffentlichen eines Assets auf Instagram (kein Scheduler/n8n).
 *
 *   POST { asset_id, caption, platform:'instagram' }
 *
 * Ablauf: Zielkonto (verbundenes IG des Kunden) auflösen → signierte,
 * öffentliche Media-URL (media_public.php) bauen → MetaClient::publishInstagram
 * (Container → Poll → publish) → Ergebnis in content_queue als 'published'.
 * Nur Admin/Manager.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/MetaClient.php';
require_once __DIR__ . '/meta_env.php';

$session = require_login();
if (($session['type'] ?? '') === 'customer' || !has_role('admin', 'manager')) {
    json_err(403, 'Nur Admin/Manager dürfen veröffentlichen.');
}
require_csrf();

$body    = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];
$assetId  = trim((string)($body['asset_id'] ?? ''));
$caption  = (string)($body['caption'] ?? '');
$platform = (string)($body['platform'] ?? 'instagram');

if ($assetId === '')            json_err(400, 'asset_id fehlt.');
if ($platform !== 'instagram')  json_err(400, 'Aktuell wird nur Instagram unterstützt.');

$asset = db_one(
    "SELECT a.id, a.project_id, a.content_type, a.status, p.customer_id
       FROM assets a
       JOIN projects p ON p.id = a.project_id
      WHERE a.id = ?",
    [$assetId]
);
if (!$asset)                        json_err(404, 'Asset nicht gefunden.');
if ($asset['status'] !== 'stored')  json_err(409, 'Asset ist noch nicht bereit (nicht gespeichert).');
requireProjectAccess((string)$asset['project_id'], $session);

$customerId = (string)($asset['customer_id'] ?? '');
if ($customerId === '') json_err(400, 'Projekt hat keinen Kunden — kein Zielkonto.');

// Verbundenes Instagram-Konto des Kunden
$acc = db_one(
    "SELECT external_id, access_token, account_label
       FROM social_accounts
      WHERE customer_id = ? AND platform = 'instagram' AND status = 'connected'
      ORDER BY updated_at DESC LIMIT 1",
    [$customerId]
);
if (!$acc || empty($acc['access_token']) || empty($acc['external_id'])) {
    json_err(409, 'Für diesen Kunden ist kein Instagram-Konto verbunden.');
}

// Signierte, öffentliche Media-URL für den Meta-Fetcher
$secret = meta_config()['app_secret'];
if ($secret === '') json_err(503, 'Meta-App nicht konfiguriert.');

$exp = time() + 1800; // 30 Min gültig
$sig = hash_hmac('sha256', $assetId . '|' . $exp, $secret);

$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$apiBase = $scheme . '://' . $host . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x')), '/');
$mediaUrl = $apiBase . '/media_public.php?a=' . urlencode($assetId) . '&exp=' . $exp . '&sig=' . $sig;

$isVideo = stripos((string)$asset['content_type'], 'video') === 0;

try {
    $meta = new MetaClient('x');
    $res  = $meta->publishInstagram(
        (string)$acc['external_id'], (string)$acc['access_token'], $mediaUrl, $caption, $isVideo
    );
} catch (\Throwable $e) {
    // Fehlversuch protokollieren
    try {
        db_exec(
            "INSERT INTO content_queue
               (customer_id, project_id, asset_id, platform, content_type, caption, media_url, status, error_message)
             VALUES (?, ?, ?, 'instagram', ?, ?, ?, 'error', ?)",
            [$customerId, $asset['project_id'], $assetId, $isVideo ? 'reel' : 'post',
             $caption, $mediaUrl, $e->getMessage()]
        );
    } catch (\Throwable $ignored) {}
    json_err(502, 'Instagram-Veröffentlichung fehlgeschlagen: ' . $e->getMessage());
}

db_exec(
    "INSERT INTO content_queue
       (customer_id, project_id, asset_id, platform, content_type, caption, media_url, status, published_at, platform_response)
     VALUES (?, ?, ?, 'instagram', ?, ?, ?, 'published', NOW(), ?)",
    [$customerId, $asset['project_id'], $assetId, $isVideo ? 'reel' : 'post',
     $caption, $mediaUrl, json_encode($res)]
);
log_activity('content', $assetId, 'published', ['platform' => 'instagram', 'ig_media' => $res['id']]);

json_ok([
    'published' => true,
    'mediaId'   => $res['id'],
    'permalink' => $res['permalink'],
    'account'   => $acc['account_label'],
]);
