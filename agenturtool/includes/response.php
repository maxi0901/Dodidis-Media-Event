<?php
declare(strict_types=1);

/**
 * Einheitliche JSON-Responses.
 * Antwort-Format ist über die gesamte API stabil:
 *   Erfolg: { "success": true,  "data": ... }
 *   Fehler: { "success": false, "error": "...", "extra": ... }
 */

// ── Zentrales Error-Logging ──────────────────────────────────────────────────
(static function (): void {
    $logFile = __DIR__ . '/../Fehler.log';

    // Alle PHP-Fehler in Fehler.log umleiten, nie im Browser anzeigen
    ini_set('log_errors',       '1');
    ini_set('display_errors',   '0');
    ini_set('display_startup_errors', '0');
    ini_set('error_log',        $logFile);
    error_reporting(E_ALL);

    // PHP-Warnungen / Notices → Fehler.log
    set_error_handler(static function (int $no, string $str, string $file, int $line) use ($logFile): bool {
        if (!(error_reporting() & $no)) {
            return false; // @ operator — nicht loggen
        }
        static $map = [
            E_ERROR => 'ERROR', E_WARNING => 'WARNING', E_NOTICE => 'NOTICE',
            E_PARSE => 'PARSE', E_DEPRECATED => 'DEPRECATED',
            E_USER_ERROR => 'USER_ERROR', E_USER_WARNING => 'USER_WARNING',
            E_USER_NOTICE => 'USER_NOTICE', E_USER_DEPRECATED => 'USER_DEPRECATED',
            E_RECOVERABLE_ERROR => 'RECOVERABLE',
        ];
        $type = $map[$no] ?? 'PHP(' . $no . ')';
        $entry = sprintf("[%s] %s: %s in %s:%d\n", date('Y-m-d H:i:s T'), $type, $str, $file, $line);
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        return true; // PHP-Standardhandler unterdrücken
    });

    // Nicht abgefangene Exceptions → Fehler.log + generischer 500
    set_exception_handler(static function (\Throwable $e) use ($logFile): void {
        $entry = sprintf(
            "[%s] UNCAUGHT %s: %s in %s:%d\nTrace:\n%s\n",
            date('Y-m-d H:i:s T'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => false, 'success' => false, 'error' => $e->getMessage()]);
        exit;
    });

    // Fatale Fehler (Parse, E_ERROR) werden vom shutdown handler gefangen
    register_shutdown_function(static function () use ($logFile): void {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE], true)) {
            $entry = sprintf(
                "[%s] FATAL %d: %s in %s:%d\n",
                date('Y-m-d H:i:s T'),
                $err['type'],
                $err['message'],
                $err['file'],
                $err['line']
            );
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        }
    });
})();
// ─────────────────────────────────────────────────────────────────────────────

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
        ['ok' => true, 'success' => true, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function json_err(int $code, string $msg, $extra = null): void
{
    record_server_error($code, $msg);
    http_response_code($code);
    json_headers();
    $payload = ['ok' => false, 'success' => false, 'error' => $msg];
    if ($extra !== null) {
        $payload['extra'] = $extra;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Hält die letzten fehlgeschlagenen API-Antworten (>=400) für die Support-
 * Diagnose fest — Ring-Puffer in app_config (max. 30 Einträge, kein Wachstum).
 * Best-effort: schlägt still fehl, wenn die DB noch nicht verfügbar ist.
 */
function record_server_error(int $code, string $msg): void
{
    if ($code < 400 || $code === 401) return;              // 401 = reines Login-Rauschen
    if (!function_exists('db_exec') || !function_exists('db_one')) return;
    try {
        $entry = [
            'time'   => date('Y-m-d H:i:s'),
            'code'   => $code,
            'msg'    => mb_substr($msg, 0, 300),
            'method' => (string)($_SERVER['REQUEST_METHOD'] ?? ''),
            'uri'    => mb_substr((string)($_SERVER['REQUEST_URI'] ?? ''), 0, 300),
        ];
        $row = db_one("SELECT value FROM app_config WHERE `key` = 'support_errors'");
        $arr = [];
        if ($row && !empty($row['value'])) {
            $d = json_decode((string)$row['value'], true);
            if (is_array($d)) $arr = $d;
        }
        $arr[] = $entry;
        if (count($arr) > 30) $arr = array_slice($arr, -30);
        db_exec(
            "INSERT INTO app_config (`key`, value) VALUES ('support_errors', ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)",
            [json_encode($arr, JSON_UNESCAPED_UNICODE)]
        );
    } catch (\Throwable $e) { /* Diagnose ist best-effort */ }
}
