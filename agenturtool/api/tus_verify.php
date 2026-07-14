<?php
declare(strict_types=1);

/**
 * Prüft das signierte Upload-Token (aus tus_ticket.php) und liefert den
 * WebDAV-Zielpfad zurück. Wird NUR vom Post-Finish-Hook auf dem vServer
 * aufgerufen (X-API-KEY), nachdem tusd die Datei vollständig gepuffert hat —
 * der Hook legt die Datei dann per WebDAV unter diesem Pfad auf dem NAS ab.
 *
 *   POST { "token": "<payload>.<hmac>" }   Header: X-API-KEY
 *   → { target: "<projektordner>/<kind>/<datei>" }
 *
 * Keine Session (Server-zu-Server). HMAC-Schlüssel = config.php api_key.
 */

require_once __DIR__ . '/../includes/response.php';

$cfg    = require __DIR__ . '/../config.php';
$apiKey = (string)($cfg['api_key'] ?? '');
$given  = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');
if ($apiKey === '' || !hash_equals($apiKey, $given)) {
    json_err(401, 'Ungültiger API-Key.');
}

$body  = json_decode((string)file_get_contents('php://input'), true);
$token = is_array($body) ? (string)($body['token'] ?? '') : (string)($_POST['token'] ?? '');
if ($token === '' || substr_count($token, '.') !== 1) json_err(400, 'Token fehlt/ungültig.');

[$payload, $sig] = explode('.', $token, 2);
$expected = hash_hmac('sha256', $payload, $apiKey);
if (!hash_equals($expected, $sig)) json_err(403, 'Token-Signatur ungültig.');

$json = base64_decode(strtr($payload, '-_', '+/'), true);
$data = $json !== false ? json_decode($json, true) : null;
if (!is_array($data) || empty($data['t'])) json_err(400, 'Token-Inhalt ungültig.');
if ((int)($data['e'] ?? 0) < time())       json_err(410, 'Token abgelaufen.');

$target = (string)$data['t'];
// Sicherheitsnetz: keine Pfad-Traversal (HMAC garantiert Herkunft, doppelt hält).
if (str_contains($target, '..') || $target === '' || $target[0] === '/') {
    json_err(400, 'Zielpfad ungültig.');
}

json_ok(['target' => $target]);
