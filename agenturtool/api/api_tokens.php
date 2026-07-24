<?php
declare(strict_types=1);

/**
 * Verwaltung der persönlichen API-Zugriffstoken (für MCP-Server / Automatisierung).
 * Jeder eingeloggte Mitarbeiter verwaltet ausschließlich SEINE eigenen Token.
 * Der Klartext-Token wird NUR einmal bei der Erstellung zurückgegeben; danach ist
 * nur noch der SHA-256-Hash gespeichert.
 *
 *   GET            → eigene Token (Metadaten, ohne Klartext)
 *   POST {label}   → neuen Token erzeugen (Klartext einmalig in der Antwort)
 *   DELETE ?id=    → eigenen Token widerrufen
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];

// Token-Verwaltung nur per echter Login-Session (nicht via Token selbst) und nur
// für Mitarbeiter — Kunden haben keine API-Token.
if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur für Mitarbeiter.');
}
if (is_api_token_request()) {
    json_err(403, 'Token können nicht per Token verwaltet werden — bitte im Browser einloggen.');
}
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

$uid = (string)$session['uid'];

switch ($method) {

    case 'GET': {
        $rows = db_all(
            "SELECT id, label, created_at AS createdAt, last_used_at AS lastUsedAt
               FROM api_tokens
              WHERE user_id = ? AND revoked_at IS NULL
              ORDER BY created_at DESC",
            [$uid]
        );
        json_ok($rows);
    }

    case 'POST': {
        $b     = input_json();
        $label = trim((string)($b['label'] ?? ''));
        if ($label === '') $label = 'MCP-Token';
        if (mb_strlen($label) > 120) $label = mb_substr($label, 0, 120);

        // Klartext-Token: 48 Hex-Zeichen mit Präfix; nur der Hash wird gespeichert.
        $token = 'dm_' . bin2hex(random_bytes(24));
        $hash  = hash('sha256', $token);
        $id    = 'tok_' . bin2hex(random_bytes(8));

        db_exec(
            "INSERT INTO api_tokens (id, user_id, token_hash, label) VALUES (?, ?, ?, ?)",
            [$id, $uid, $hash, $label]
        );
        log_activity('api_token', $id, 'created', ['label' => $label]);

        // Klartext NUR hier — wird nie wieder ausgegeben.
        json_ok(['id' => $id, 'label' => $label, 'token' => $token], 201);
    }

    case 'DELETE': {
        $id = (string)($_GET['id'] ?? '');
        if ($id === '') json_err(400, 'id fehlt.');
        $t = db_one("SELECT id FROM api_tokens WHERE id = ? AND user_id = ? AND revoked_at IS NULL", [$id, $uid]);
        if (!$t) json_err(404, 'Token nicht gefunden.');
        db_exec("UPDATE api_tokens SET revoked_at = NOW() WHERE id = ? AND user_id = ?", [$id, $uid]);
        log_activity('api_token', $id, 'revoked');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
