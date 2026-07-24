<?php
declare(strict_types=1);

require_once __DIR__ . '/response.php';

/**
 * Startet die PHP-Session mit sicheren Cookie-Parametern.
 * Idempotent – kann mehrfach aufgerufen werden.
 */
function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $cfg = require __DIR__ . '/../config.php';

    // GC muss server-seitig mindestens so lang wie die längste mögliche Cookie-Lifetime
    $gcMax = max($cfg['session_max_age'], $cfg['session_remember_age'] ?? $cfg['session_max_age']);
    ini_set('session.gc_maxlifetime', (string)$gcMax);

    session_name($cfg['session_name']);
    session_set_cookie_params([
        'lifetime' => 0,  // Default: Session-Cookie (läuft beim Browser-Schließen ab)
        'path'     => $cfg['cookie_path'],
        'secure'   => (bool)$cfg['cookie_secure'],
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Sliding expiry: Timer läuft ab dem letzten Besuch, nicht ab Login
    if (!empty($_SESSION['uid']) || !empty($_SESSION['cid'])) {
        $age      = !empty($_SESSION['remember'])
            ? ($cfg['session_remember_age'] ?? $cfg['session_max_age'])
            : $cfg['session_max_age'];
        $lastSeen = (int)($_SESSION['last_seen'] ?? $_SESSION['started_at'] ?? 0);
        if ($lastSeen > 0 && (time() - $lastSeen) > $age) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
            session_start();
        }
    }
}

/**
 * Erzwingt eine aktive Session. Liefert das Session-Bundle.
 */
function require_login(): array
{
    start_app_session();
    if (empty($_SESSION['uid']) && empty($_SESSION['cid'])) {
        json_err(401, 'Nicht angemeldet.');
    }
    // Aktivität markieren (sliding session)
    $_SESSION['last_seen'] = time();
    return current_session();
}

/**
 * Liefert das aktuelle Session-Bundle (oder null, wenn keine Session).
 */
function current_session(): array
{
    return [
        'type'            => $_SESSION['type'] ?? null,
        'uid'             => $_SESSION['uid']  ?? null,
        'cid'             => $_SESSION['cid']  ?? null,
        'name'            => $_SESSION['name'] ?? null,
        'roles'           => $_SESSION['roles'] ?? [],
        'customer_number' => $_SESSION['customer_number'] ?? null,
        'csrf'            => $_SESSION['csrf'] ?? '',
    ];
}

/**
 * Prüft, ob die Session mind. eine der erlaubten Rollen besitzt.
 * Bei Customer-Sessions wird die virtuelle Rolle 'customer' geprüft.
 */
function has_role(string ...$roles): bool
{
    if (($_SESSION['type'] ?? '') === 'customer') {
        return in_array('customer', $roles, true);
    }
    $mine = $_SESSION['roles'] ?? [];
    // Supportmitarbeiter = interner Superuser: besteht jede Mitarbeiter-Rollen-
    // prüfung (Vollzugriff für Fehleranalyse). Kunden-Checks bleiben ausgenommen.
    if (in_array('support', $mine, true) && !in_array('customer', $roles, true)) {
        return true;
    }
    foreach ($roles as $r) {
        if (in_array($r, $mine, true)) return true;
    }
    return false;
}

function require_role(string ...$roles): void
{
    if (!has_role(...$roles)) {
        json_err(403, 'Keine Berechtigung.');
    }
}

/**
 * Erzwingt CSRF-Token bei zustandsändernden Requests.
 */
function require_csrf(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $have = $_SESSION['csrf'] ?? '';
    if ($sent === '' || $have === '' || !hash_equals($have, $sent)) {
        json_err(403, 'CSRF-Token ungültig.');
    }
}

/**
 * Setzt die Session nach erfolgreichem Login frisch auf und rotiert die SID.
 * $remember=true → setzt einen langlebigen Cookie (session_remember_age).
 */
function regenerate_session(bool $remember = false): void
{
    $cfg = require __DIR__ . '/../config.php';

    session_regenerate_id(true);
    $_SESSION['started_at'] = time();
    $_SESSION['last_seen']  = time();
    $_SESSION['csrf']       = bin2hex(random_bytes(16));
    $_SESSION['remember']   = $remember;

    if ($remember) {
        $age = $cfg['session_remember_age'] ?? $cfg['session_max_age'];
        setcookie(
            session_name(),
            session_id(),
            time() + $age,
            $cfg['cookie_path'],
            '',
            (bool)$cfg['cookie_secure'],
            true
        );
    }
}
