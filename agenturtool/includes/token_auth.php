<?php
declare(strict_types=1);

/**
 * Persönliche Bearer-Token-Authentifizierung für die HTTP-API (MCP-Server /
 * Automatisierung). Anders als api_auth.php (globaler n8n-X-API-KEY) gehört ein
 * Token hier GENAU EINEM Benutzer und authentifiziert den Request als diesen —
 * mit dessen Rollen/Rechten und unter dessen ID im Aktivitätslog.
 *
 * Es wird NUR der SHA-256-Hash gespeichert (Klartext nur einmal bei Erstellung).
 *
 * Zentral aus require_login() aufgerufen: liegt keine Cookie-Session vor, aber
 * ein gültiger Bearer-Token, wird die Session-Umgebung in-memory für DIESEN
 * Request befüllt. require_csrf() überspringt Token-Requests (kein Cookie → kein
 * CSRF-Risiko; der Token wird nicht automatisch vom Browser mitgesendet).
 */

/** Liest den Bearer-Token aus dem Authorization-Header (leer, wenn keiner). */
function api_bearer_token(): string
{
    $h = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($h === '' && function_exists('apache_request_headers')) {
        foreach (apache_request_headers() as $k => $v) {
            if (strcasecmp((string)$k, 'Authorization') === 0) { $h = (string)$v; break; }
        }
    }
    if (stripos($h, 'Bearer ') === 0) {
        return trim(substr($h, 7));
    }
    return '';
}

/**
 * Versucht, den Request per Bearer-Token zu authentifizieren. Erfolgreich →
 * befüllt $_SESSION (type/uid/name/roles) und setzt das Token-Auth-Flag.
 * @return bool true, wenn ein gültiger Token akzeptiert wurde.
 */
function try_api_token_auth(): bool
{
    $tok = api_bearer_token();
    if ($tok === '' || !function_exists('db_one')) return false;

    $hash = hash('sha256', $tok);
    try {
        $row = db_one(
            "SELECT t.id AS tokenId, t.user_id AS userId, u.name AS name
               FROM api_tokens t
               JOIN users u ON u.id = t.user_id
              WHERE t.token_hash = ? AND t.revoked_at IS NULL
              LIMIT 1",
            [$hash]
        );
    } catch (\Throwable $e) {
        return false; // Tabelle evtl. noch nicht migriert
    }
    if (!$row) return false;

    $roles = [];
    try {
        $roles = array_column(
            db_all("SELECT role_name FROM user_roles WHERE user_id = ?", [$row['userId']]),
            'role_name'
        );
    } catch (\Throwable $e) { /* ohne Rollen weiter (leer) */ }

    $_SESSION['type']  = 'staff';
    $_SESSION['uid']   = $row['userId'];
    $_SESSION['name']  = $row['name'];
    $_SESSION['roles'] = $roles;
    $GLOBALS['__api_token_auth'] = true;

    try { db_exec("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?", [$row['tokenId']]); }
    catch (\Throwable $e) { /* best effort */ }

    return true;
}

/** True, wenn der aktuelle Request per persönlichem API-Token (nicht Cookie) läuft. */
function is_api_token_request(): bool
{
    return !empty($GLOBALS['__api_token_auth']);
}
