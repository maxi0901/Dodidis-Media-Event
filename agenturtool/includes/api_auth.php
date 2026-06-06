<?php
declare(strict_types=1);

/**
 * Authentifizierung + JSON-Ausgabe für die externe n8n-Schnittstelle.
 *
 * Bewusst getrennt vom Session-/Login-System (auth-check.php): diese Endpunkte
 * werden Maschine-zu-Maschine über den Header X-API-KEY abgesichert, NICHT über
 * Cookies/Session/CSRF. Das bestehende Login bleibt davon unberührt.
 */

require_once __DIR__ . '/response.php';   // zentrale Fehlerunterdrückung + Handler
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';    // input_json(), s(), …

/**
 * Sendet eine JSON-Antwort im exakt vereinbarten Format und beendet das Skript.
 * (Bewusst eigener Sender statt json_ok/json_err, deren Schema {ok,success,data}
 *  von der n8n-Vertragsstruktur abweicht.)
 */
function api_send(int $code, array $payload): void
{
    http_response_code($code);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Prüft den X-API-KEY-Header gegen config.php['api_key'].
 * Fail-closed: leerer oder falscher Key → 401 { success:false, error:"Unauthorized" }.
 * Vergleich über hash_equals (timing-sicher).
 */
function require_api_key(): void
{
    $cfg      = require __DIR__ . '/../config.php';
    $expected = (string)($cfg['api_key'] ?? '');
    $sent     = (string)($_SERVER['HTTP_X_API_KEY'] ?? '');

    if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
        api_send(401, ['success' => false, 'error' => 'Unauthorized']);
    }
}

/**
 * Prüft, ob eine Spalte existiert (honoriert „… speichern, falls Spalte vorhanden").
 * Robust gegenüber abweichenden Bestandstabellen.
 */
function api_has_column(string $table, string $col): bool
{
    $row = db_one(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $col]
    );
    return (int)($row['c'] ?? 0) > 0;
}

/**
 * Liest und validiert content_id aus dem JSON-Body. Bricht bei ungültigem Wert mit 400 ab.
 */
function api_require_content_id(array $body): int
{
    $raw = $body['content_id'] ?? null;
    if (!is_numeric($raw) || (int)$raw <= 0) {
        api_send(400, ['success' => false, 'error' => 'content_id required']);
    }
    return (int)$raw;
}
