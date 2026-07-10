<?php
declare(strict_types=1);

/**
 * Gemeinsame Publish-Logik für Instagram (direkt & geplant).
 * Genutzt von content_publish_now.php (manuell) und publish_due.php (Cron).
 */

require_once __DIR__ . '/MetaClient.php';
require_once __DIR__ . '/meta_env.php';

/**
 * Veröffentlicht ein finales Asset direkt auf dem verbundenen Instagram-Konto
 * des zugehörigen Kunden. Baut eine signierte, öffentliche Media-URL für den
 * Meta-Fetcher (30 Min gültig; bevorzugt die lokale media_cache-Kopie).
 *
 * @param array $asset  Zeile mit id, customer_id, content_type
 * @return array{id:string, permalink:?string}
 * @throws \RuntimeException bei fehlendem Konto / Fehler
 */
function ig_publish_asset(array $asset, string $caption): array
{
    $assetId    = (string)($asset['id'] ?? '');
    $customerId = (string)($asset['customer_id'] ?? '');
    if ($assetId === '')    throw new \RuntimeException('Asset-ID fehlt.');
    if ($customerId === '') throw new \RuntimeException('Projekt hat keinen Kunden — kein Zielkonto.');

    $acc = db_one(
        "SELECT external_id, access_token FROM social_accounts
          WHERE customer_id = ? AND platform = 'instagram' AND status = 'connected'
          ORDER BY updated_at DESC LIMIT 1",
        [$customerId]
    );
    if (!$acc || empty($acc['access_token']) || empty($acc['external_id'])) {
        throw new \RuntimeException('Für diesen Kunden ist kein Instagram-Konto verbunden.');
    }

    $secret = meta_config()['app_secret'];
    if ($secret === '') throw new \RuntimeException('Meta-App nicht konfiguriert.');

    // Signierte, öffentliche Media-URL für Metas Fetcher
    $exp = time() + 1800;
    $sig = hash_hmac('sha256', $assetId . '|' . $exp, $secret);
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $apiBase  = $scheme . '://' . $host . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x')), '/');
    $mediaUrl = $apiBase . '/media_public.php?a=' . urlencode($assetId) . '&exp=' . $exp . '&sig=' . $sig;

    $isVideo = stripos((string)($asset['content_type'] ?? ''), 'video') === 0;

    $meta = new MetaClient('x');
    return $meta->publishInstagram(
        (string)$acc['external_id'], (string)$acc['access_token'], $mediaUrl, $caption, $isVideo
    );
}
