<?php
/**
 * Agenturtool – REST-API (MySQL-Backend)
 *
 * Endpunkte:
 *   GET  ?action=ping  – Health-Check (DB-Verbindung testen)
 *   GET  ?action=pull  – Komplettes Daten-Snapshot zurückgeben
 *   POST ?action=push  – Upserts + Deletions in einer Transaktion anwenden
 *
 * Request-Body für push (JSON):
 *   {
 *     "upserts":   { "<table>": [ { "id": "...", "data": {...} }, ... ], "app_config": [ { "key": "...", "value": {...} } ] },
 *     "deletions": { "<table>": ["id1","id2", ...] }
 *   }
 *
 * Erlaubte Tabellen: users, customers, projects, vacations, todos, shoot_days, app_config
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$cfg = require __DIR__ . '/config.php';

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function bad(string $msg, int $code = 400): void
{
    respond(['ok' => false, 'error' => $msg], $code);
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['host'],
        (int) $cfg['port'],
        $cfg['database'],
        $cfg['charset']
    );
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (Throwable $e) {
    bad('DB-Verbindung fehlgeschlagen: ' . $e->getMessage(), 500);
}

$action = $_GET['action'] ?? '';

// Whitelist der erlaubten "id+data"-Tabellen (alle bis auf app_config)
$DATA_TABLES = ['users', 'customers', 'projects', 'vacations', 'todos', 'shoot_days'];

function hasDataColumn(PDO $pdo, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) return $cache[$table];
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE 'data'");
    $stmt->execute();
    $cache[$table] = (bool) $stmt->fetch();
    return $cache[$table];
}


if ($action === 'ping') {
    $row = $pdo->query('SELECT 1 AS ok')->fetch();
    respond(['ok' => true, 'db' => $cfg['database'], 'ping' => $row['ok'] ?? null]);
}

if ($action === 'pull') {
    $out = [];
    foreach ($DATA_TABLES as $t) {
        $rows = [];
        if (hasDataColumn($pdo, $t)) {
            $stmt = $pdo->query("SELECT `data` FROM `$t`");
            foreach ($stmt as $r) {
                $decoded = json_decode($r['data'], true);
                if ($decoded !== null) {
                    $rows[] = $decoded;
                }
            }
        } else {
            $stmt = $pdo->query("SELECT * FROM `$t`");
            foreach ($stmt as $r) {
                unset($r['created_at'], $r['updated_at']);
                $rows[] = $r;
            }
        }
        $out[$t] = $rows;
    }
    $stmt = $pdo->query('SELECT `key`, `value` FROM `app_config`');
    $cfgRows = [];
    foreach ($stmt as $r) {
        $cfgRows[] = ['key' => $r['key'], 'value' => json_decode($r['value'], true)];
    }
    $out['app_config'] = $cfgRows;
    respond(['ok' => true, 'data' => $out]);
}

if ($action === 'push') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        bad('POST erwartet', 405);
    }
    $raw = file_get_contents('php://input');
    if (!$raw) {
        bad('Leerer Request-Body');
    }
    $body = json_decode($raw, true);
    if (!is_array($body)) {
        bad('Body ist kein gültiges JSON');
    }

    $upserts   = is_array($body['upserts']   ?? null) ? $body['upserts']   : [];
    $deletions = is_array($body['deletions'] ?? null) ? $body['deletions'] : [];

    $pdo->beginTransaction();
    try {
        // Upserts in JSON-Datentabellen
        foreach ($DATA_TABLES as $t) {
            if (empty($upserts[$t]) || !is_array($upserts[$t])) {
                continue;
            }
            if (hasDataColumn($pdo, $t)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO `$t` (`id`, `data`) VALUES (:id, :data) AS new
                     ON DUPLICATE KEY UPDATE `data` = new.`data`, `updated_at` = CURRENT_TIMESTAMP"
                );
                foreach ($upserts[$t] as $row) {
                    if (!isset($row['id'], $row['data'])) {
                        continue;
                    }
                    $stmt->execute([
                        ':id'   => (string) $row['id'],
                        ':data' => json_encode($row['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            }
        }

        // Upserts in app_config
        if (!empty($upserts['app_config']) && is_array($upserts['app_config'])) {
            $stmt = $pdo->prepare(
                "INSERT INTO `app_config` (`key`, `value`) VALUES (:key, :value) AS new
                 ON DUPLICATE KEY UPDATE `value` = new.`value`, `updated_at` = CURRENT_TIMESTAMP"
            );
            foreach ($upserts['app_config'] as $row) {
                if (!isset($row['key'])) {
                    continue;
                }
                $stmt->execute([
                    ':key'   => (string) $row['key'],
                    ':value' => json_encode($row['value'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        }

        // Deletions
        foreach ($DATA_TABLES as $t) {
            $ids = $deletions[$t] ?? [];
            if (!is_array($ids) || empty($ids)) {
                continue;
            }
            $ids = array_values(array_map('strval', $ids));
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM `$t` WHERE `id` IN ($placeholders)");
            $stmt->execute($ids);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        bad('Push fehlgeschlagen: ' . $e->getMessage(), 500);
    }

    respond(['ok' => true]);
}

bad('Unbekannte action. Erlaubt: ping, pull, push', 404);
