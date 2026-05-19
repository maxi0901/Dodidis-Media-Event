<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';

/**
 * Liest JSON-Body von POST/PUT-Requests.
 */
function input_json(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

/**
 * Erzeugt eine neue ID im Schema "<prefix>_<8hex>" – kompatibel zu den
 * client-seitig erzeugten IDs aus index.html (uid()-Helper).
 */
function uid(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(4));
}

/**
 * Trim + Längenbegrenzung (UTF-8 safe). Leerstring -> null.
 */
function s(?string $v, int $max = 255): ?string
{
    if ($v === null) return null;
    $v = trim($v);
    if ($v === '') return null;
    return mb_substr($v, 0, $max);
}

/**
 * Normalisiert Datum-Strings auf YYYY-MM-DD. Leer/ungültig -> null.
 */
function as_date(?string $v): ?string
{
    if (!$v) return null;
    $ts = strtotime($v);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
}

/**
 * Normalisiert HH:MM(:SS) Strings. Leer/ungültig -> null.
 */
function as_time(?string $v): ?string
{
    if (!$v) return null;
    if (preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $v, $m)) {
        return sprintf('%02d:%02d:%02d', (int)$m[1], (int)$m[2], (int)($m[3] ?? 0));
    }
    return null;
}

function as_bool($v): int
{
    return ($v === true || $v === 1 || $v === '1' || $v === 'true' || $v === 'on') ? 1 : 0;
}

function as_dec($v): ?string
{
    if ($v === null || $v === '') return null;
    if (!is_numeric($v)) return null;
    return (string)$v;
}

function as_int($v): ?int
{
    if ($v === null || $v === '') return null;
    return (int)$v;
}

/**
 * CSRF-Token holen oder neu generieren (innerhalb laufender Session).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/**
 * Schreibt einen Eintrag in activity_log.
 */
function log_activity(string $scope, ?string $refId, string $action, array $details = []): void
{
    try {
        $actor = $_SESSION['uid'] ?? null;
        $stmt = db()->prepare(
            "INSERT INTO activity_log (scope, ref_id, action, actor_id, details)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            mb_substr($scope, 0, 32),
            $refId,
            mb_substr($action, 0, 64),
            $actor,
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ]);
    } catch (Throwable $e) {
        // Activity-Log darf den Request nie kippen.
        error_log('[agenturtool/log_activity] ' . $e->getMessage());
    }
}

function log_err(string $msg, array $ctx = []): void
{
    error_log('[agenturtool] ' . $msg . ($ctx ? ' ' . json_encode($ctx) : ''));
}

/**
 * Liefert ein User-Objekt im Format, das das Frontend erwartet
 * (mit roles-Array, ohne password_hash).
 */
function hydrate_user(string $userId): ?array
{
    $nameCol = users_name_column();
    $u = db_one("SELECT id, username, {$nameCol} AS name, email, avatar_color, avatar_image, calendar_prefs
                 FROM users WHERE id = ?", [$userId]);
    if (!$u) return null;
    $roles = db_all("SELECT role FROM user_roles WHERE user_id = ?", [$userId]);
    $u['roles'] = array_column($roles, 'role');
    if ($u['calendar_prefs']) {
        $u['calendarPrefs'] = json_decode($u['calendar_prefs'], true);
    } else {
        $u['calendarPrefs'] = null;
    }
    unset($u['calendar_prefs']);
    if ($u['avatar_image']) {
        $u['avatarImage'] = $u['avatar_image'];
    }
    unset($u['avatar_image']);
    if ($u['avatar_color']) {
        $u['avatarColor'] = $u['avatar_color'];
    }
    unset($u['avatar_color']);
    return $u;
}

/**
 * Maximaler Schutz: garantiert, dass kein password_hash/pin_hash in ein Response-Array kommt.
 */
function strip_secrets(array $row): array
{
    unset($row['password_hash'], $row['password'], $row['pin_hash'], $row['pin']);
    return $row;
}

/**
 * Kompatibilitätsschicht: manche Deployments verwenden `users.name`,
 * andere `users.full_name`.
 */
function users_name_column(): string
{
    static $col = null;
    if ($col !== null) return $col;

    $row = db_one(
        "SELECT column_name
           FROM information_schema.columns
          WHERE table_schema = DATABASE()
            AND table_name = 'users'
            AND column_name IN ('name','full_name')
          ORDER BY (column_name = 'name') DESC
          LIMIT 1"
    );
    $col = $row['column_name'] ?? 'name';
    return $col;
}
