<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = $_GET['id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

switch ($method) {

    case 'GET': {
        $nameCol = users_name_column();
        if ($id) {
            // Detail: admin/manager beliebig, self immer erlaubt
            if (!has_role('admin', 'manager') && $session['uid'] !== $id) {
                json_err(403, 'Keine Berechtigung.');
            }
            $u = hydrate_user($id);
            if (!$u) json_err(404, 'User nicht gefunden.');
            json_ok($u);
        }

        // Liste
        // Mitarbeiter dürfen die Liste lesen (für Avatar-Anzeigen, Assignee-Auswahl etc.),
        // aber wir liefern keine Mail-Adressen außer für admin/manager.
        $isPriv  = has_role('admin', 'manager');
        $rows    = db_all("SELECT id, username, {$nameCol} AS name, " .
                          ($isPriv ? "email, " : "") .
                          "avatar_color, avatar_image FROM users ORDER BY {$nameCol}");
        $roleAll = db_all("SELECT user_id, role_name FROM user_roles");
        $byUser  = [];
        foreach ($roleAll as $r) {
            $byUser[$r['user_id']][] = $r['role_name'];
        }
        foreach ($rows as &$r) {
            $r['roles']       = $byUser[$r['id']] ?? [];
            if (isset($r['avatar_image'])) { $r['avatarImage'] = $r['avatar_image']; unset($r['avatar_image']); }
            if (isset($r['avatar_color'])) { $r['avatarColor'] = $r['avatar_color']; unset($r['avatar_color']); }
        }
        unset($r);
        json_ok($rows);
    }

    case 'POST': {
        require_role('admin');
        $nameCol = users_name_column();
        $b = input_json();
        $username = s($b['username'] ?? null, 64);
        $name     = s($b['name'] ?? null, 128);
        $hash     = (string)($b['password_hash'] ?? '');
        $email    = s($b['email'] ?? null, 190);
        $roles    = array_values(array_filter((array)($b['roles'] ?? []), 'is_string'));
        if (!$username || !$name || $hash === '') {
            json_err(400, 'username, name und password_hash sind Pflicht.');
        }
        if (!str_starts_with($hash, 'sha256$')) {
            json_err(400, 'password_hash muss mit "sha256$" beginnen.');
        }
        $newId = uid('u');

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "INSERT INTO users (id, username, {$nameCol}, email, password_hash, avatar_color, avatar_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $newId, $username, $name, $email, $hash,
                s($b['avatarColor'] ?? null, 7),
                $b['avatarImage'] ?? null,
            ]);
            insert_roles($newId, $roles);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            log_err('user create failed', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte User nicht anlegen.');
        }
        log_activity('user', $newId, 'created');
        json_ok(hydrate_user($newId), 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        $isAdmin = has_role('admin');
        $isSelf  = $session['uid'] === $id;
        if (!$isAdmin && !$isSelf) {
            json_err(403, 'Keine Berechtigung.');
        }
        $b = input_json();
        $existing = db_one("SELECT id FROM users WHERE id = ?", [$id]);
        if (!$existing) json_err(404, 'User nicht gefunden.');

        $fields = [];
        $params = [];

        if (array_key_exists('username', $b) && $isAdmin) {
            $fields[] = 'username = ?'; $params[] = s($b['username'], 64);
        }
        if (array_key_exists('name', $b)) {
            $fields[] = users_name_column() . ' = ?'; $params[] = s($b['name'], 128);
        }
        if (array_key_exists('email', $b)) {
            $fields[] = 'email = ?'; $params[] = s($b['email'], 190);
        }
        if (array_key_exists('password_hash', $b) && $b['password_hash']) {
            $h = (string)$b['password_hash'];
            if (!str_starts_with($h, 'sha256$')) json_err(400, 'password_hash muss mit "sha256$" beginnen.');
            $fields[] = 'password_hash = ?'; $params[] = $h;
        }
        if (array_key_exists('avatarColor', $b)) {
            $fields[] = 'avatar_color = ?'; $params[] = s($b['avatarColor'], 7);
        }
        if (array_key_exists('avatarImage', $b)) {
            $fields[] = 'avatar_image = ?'; $params[] = $b['avatarImage'] ?: null;
        }
        if (array_key_exists('calendarPrefs', $b)) {
            $fields[] = 'calendar_prefs = ?';
            $params[] = $b['calendarPrefs'] ? json_encode($b['calendarPrefs'], JSON_UNESCAPED_UNICODE) : null;
        }

        if ($fields) {
            $params[] = $id;
            db_exec("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?", $params);
        }

        if (array_key_exists('roles', $b) && $isAdmin) {
            db_exec("DELETE FROM user_roles WHERE user_id = ?", [$id]);
            insert_roles($id, (array)$b['roles']);
            // Wenn der bearbeitete User die eigene Session ist, Roles in Session aktualisieren
            if ($isSelf) {
                $rolesRows = db_all("SELECT role_name FROM user_roles WHERE user_id = ?", [$id]);
                $_SESSION['roles'] = array_column($rolesRows, 'role_name');
            }
        }

        log_activity('user', $id, 'edited');
        json_ok(hydrate_user($id));
    }

    case 'DELETE': {
        require_role('admin');
        if (!$id) json_err(400, 'id fehlt.');
        if ($id === $session['uid']) json_err(400, 'Eigener User kann nicht gelöscht werden.');
        db_exec("DELETE FROM users WHERE id = ?", [$id]);
        log_activity('user', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}

function insert_roles(string $userId, array $roles): void
{
    $valid = ['admin','manager','videograf','cutter','mitarbeiter'];
    $stmt  = db()->prepare("INSERT INTO user_roles (user_id, role_name) VALUES (?, ?)");
    foreach (array_unique($roles) as $r) {
        if (!in_array($r, $valid, true)) continue;
        $stmt->execute([$userId, $r]);
    }
}
