<?php
declare(strict_types=1);

/**
 * Startet den Meta-OAuth-Flow, um das Social-Konto EINES Kunden mit der App
 * zu verbinden. Nur Admin/Manager. Browser-Redirect (kein JSON).
 *
 * Aufruf:  /agenturtool/api/meta_connect.php?customer_id=cust_ab12
 * Danach:  Weiterleitung zu Meta → nach Zustimmung zurück zu meta_callback.php
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/MetaClient.php';

$session = require_login();
if (($session['type'] ?? '') === 'customer' || !has_role('admin', 'manager')) {
    json_err(403, 'Nur Manager oder Admins dürfen Konten verbinden.');
}

$customerId = trim((string)($_GET['customer_id'] ?? ''));
if ($customerId === '') json_err(400, 'customer_id fehlt.');
$cust = db_one("SELECT id, name FROM customers WHERE id = ?", [$customerId]);
if (!$cust) json_err(404, 'Kunde nicht gefunden.');

// Redirect-URI: muss EXAKT der in der Meta-App hinterlegten entsprechen.
// Vorrang hat die explizit gesetzte META_REDIRECT_URI (wichtig hinter Reverse-
// Proxies, wo PHP nur interne Scheme/Host/Pfad sieht → sonst redirect-uri-
// mismatch). Nur ohne Override wird sie aus dem Request abgeleitet.
// Quelle: meta_config() (Env zuerst, dann config.meta.php — siehe meta_env.php).
require_once __DIR__ . '/meta_env.php';
$envRedirect = meta_config()['redirect_uri'];
if ($envRedirect !== '') {
    $redirectUri = $envRedirect;
} else {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $apiBase = $scheme . '://' . $host . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x')), '/');
    $redirectUri = $apiBase . '/meta_callback.php';
}

try {
    $meta = new MetaClient($redirectUri);
} catch (\Throwable $e) {
    // App noch nicht konfiguriert → verständliche Meldung statt Redirect ins Leere
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Meta-App nicht konfiguriert</h1><p>' . htmlspecialchars($e->getMessage())
       . '</p><p>META_APP_ID und META_APP_SECRET müssen als Umgebungsvariablen gesetzt sein.</p>';
    exit;
}

// CSRF-/Replay-Schutz: State-Token in der Session ablegen und beim Callback prüfen.
$state = bin2hex(random_bytes(16));
$_SESSION['meta_oauth'] = [
    'state'        => $state,
    'customer_id'  => $customerId,
    'redirect_uri' => $redirectUri,
    'ts'           => time(),
];

header('Location: ' . $meta->loginUrl($state), true, 302);
exit;
