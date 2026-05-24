<?php
declare(strict_types=1);

opcache_reset();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    $config = require __DIR__ . '/../config.php';
    echo json_encode([
        'db' => $config['database'] ?? null,
        'table_check' => 'users',
        'role_exists' => true,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

start_app_session();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($action) {

    // ---------- ME ----------
    case 'me': {
        if ($method !== 'GET') json_err(405, 'GET erwartet.');
        if (empty($_SESSION['uid']) && empty($_SESSION['cid'])) {
            json_err(401, 'Nicht angemeldet.');
        }
        if (!empty($_SESSION['uid'])) {
            $user = hydrate_user($_SESSION['uid']);
            if (!$user) {
                // Session zeigt auf einen gelöschten User -> abräumen
                $_SESSION = [];
                session_destroy();
                json_err(401, 'Nicht angemeldet.');
            }
            json_ok([
                'type'  => 'staff',
                'user'  => $user,
                'csrf'  => csrf_token(),
            ]);
        }
        // Customer
        $cust = db_one(
            "SELECT id, name, customer_number FROM customers WHERE id = ?",
            [$_SESSION['cid']]
        );
        if (!$cust) {
            $_SESSION = [];
            session_destroy();
            json_err(401, 'Nicht angemeldet.');
        }
        json_ok([
            'type'     => 'customer',
            'customer' => $cust,
            'csrf'     => csrf_token(),
        ]);
        break;
    }

    // ---------- LOGOUT ----------
    case 'logout': {
        if ($method !== 'POST' && $method !== 'GET') json_err(405, 'POST erwartet.');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        json_ok(['logged_out' => true]);
        break;
    }

    // ---------- LOGIN ----------
    case 'login': {
        if ($method !== 'POST') json_err(405, 'POST erwartet.');
        $b = input_json();
        $type = $b['type'] ?? 'staff';

        if ($type === 'staff') {
            $nameCol = users_name_column();
            $username = trim((string)($b['username'] ?? ''));
            $hash     = (string)($b['password_hash'] ?? '');
            if ($username === '') {
                json_err(400, 'Benutzername fehlt.');
            }

            $u = db_one(
                "SELECT id, {$nameCol} AS name, username, password_hash
                   FROM users
                  WHERE LOWER(username) = LOWER(?)
                  LIMIT 1",
                [$username]
            );

            if (!$u) {
                json_err(401, 'Benutzername „' . $username . '" nicht gefunden.');
            }

            $noPasswordSet = !isset($u['password_hash']) || (string)$u['password_hash'] === '';

            if ($noPasswordSet && $hash !== '') {
                // Hat ein Passwort eingegeben, aber keins gesetzt → Hinweis auf Erst-Login
                json_err(401, 'Für diesen Account ist noch kein Passwort gesetzt. Passwortfeld leer lassen für den Erst-Login.');
            } elseif (!$noPasswordSet && $hash === '') {
                json_err(401, 'Bitte Passwort eingeben.');
            } elseif (!$noPasswordSet && !hash_equals((string)$u['password_hash'], $hash)) {
                json_err(401, 'Passwort ist falsch.');
            }

            $rolesRows = db_all("SELECT role_name FROM user_roles WHERE user_id = ?", [$u['id']]);
            $roles     = array_column($rolesRows, 'role_name');

            regenerate_session();
            $_SESSION['type']    = 'staff';
            $_SESSION['uid']     = $u['id'];
            $_SESSION['name']    = $u['name'];
            $_SESSION['roles']   = $roles;
            if ($noPasswordSet) {
                $_SESSION['must_set_password'] = true;
            }

            log_activity('auth', $u['id'], 'login', ['type' => 'staff', 'first_login' => $noPasswordSet]);

            json_ok([
                'type'              => 'staff',
                'user'              => hydrate_user($u['id']),
                'roles'             => $roles,
                'csrf'              => csrf_token(),
                'must_set_password' => $noPasswordSet,
            ]);
        }

        if ($type === 'customer') {
            $num     = trim((string)($b['customer_number'] ?? ''));
            $pinHash = (string)($b['pin_hash'] ?? '');
            if ($num === '' || $pinHash === '') {
                json_err(400, 'Kundennummer oder PIN fehlt.');
            }

            $c = db_one(
                "SELECT id, name, customer_number, pin_hash
                   FROM customers
                  WHERE customer_number = ?
                  LIMIT 1",
                [$num]
            );

            if (!$c) {
                json_err(401, 'Kundennummer oder PIN falsch.');
            }

            if (!$c['pin_hash']) {
                // Erst-Login: PIN MUSS gleich Kundennummer sein
                $expected = 'sha256$' . hash('sha256', $c['customer_number']);
                if (!hash_equals($expected, $pinHash)) {
                    json_err(401, 'Erst-Login: nimm bitte deine Kundennummer als PIN.');
                }
                db_exec(
                    "UPDATE customers SET pin_hash = ? WHERE id = ?",
                    [$expected, $c['id']]
                );
                $c['pin_hash'] = $expected;
            } else {
                if (!hash_equals((string)$c['pin_hash'], $pinHash)) {
                    json_err(401, 'Kundennummer oder PIN falsch.');
                }
            }

            regenerate_session();
            $_SESSION['type']            = 'customer';
            $_SESSION['cid']             = $c['id'];
            $_SESSION['name']            = $c['name'];
            $_SESSION['customer_number'] = $c['customer_number'];

            log_activity('auth', $c['id'], 'login', ['type' => 'customer']);

            json_ok([
                'type'     => 'customer',
                'customer' => ['id' => $c['id'], 'name' => $c['name'], 'customer_number' => $c['customer_number']],
                'csrf'     => csrf_token(),
            ]);
        }

        json_err(400, 'Unbekannter Login-Typ.');
        break;
    }

    // ---------- SET PASSWORD (Erst-Login) ----------
    case 'set_password': {
        if ($method !== 'POST') json_err(405, 'POST erwartet.');
        start_app_session();
        if (empty($_SESSION['uid'])) json_err(401, 'Nicht angemeldet.');

        $b    = input_json();
        $hash = trim((string)($b['password_hash'] ?? ''));
        if (!str_starts_with($hash, 'sha256$') || strlen($hash) < 71) {
            json_err(400, 'Ungültiges Passwort-Format.');
        }

        db_exec("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $_SESSION['uid']]);
        unset($_SESSION['must_set_password']);

        log_activity('auth', $_SESSION['uid'], 'password_set', []);

        json_ok(['password_set' => true, 'csrf' => csrf_token()]);
        break;
    }

    default:
        json_err(404, 'Unbekannte Aktion.');
}
