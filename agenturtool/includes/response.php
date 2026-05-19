<?php
declare(strict_types=1);

/**
 * Einheitliche JSON-Responses.
 * Antwort-Format ist über die gesamte API stabil:
 *   Erfolg: { "success": true,  "data": ... }
 *   Fehler: { "success": false, "error": "...", "extra": ... }
 */

function json_headers(): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }
}

function json_ok($data = [], int $code = 200): void
{
    http_response_code($code);
    json_headers();
    echo json_encode(
        ['success' => true, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function json_err(int $code, string $msg, $extra = null): void
{
    http_response_code($code);
    json_headers();
    $payload = ['success' => false, 'error' => $msg];
    if ($extra !== null) {
        $payload['extra'] = $extra;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
