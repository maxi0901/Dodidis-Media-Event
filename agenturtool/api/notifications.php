<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');

$method = $_SERVER['REQUEST_METHOD'];
$uid    = $session['uid'];

if ($method === 'GET') {
    try {
        $rows = db_all(
            "SELECT id, type, title, body, ref_id, ref_type, seen_at, created_at AS createdAt
               FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [$uid]
        );
    } catch (\Throwable $e) {
        // Only suppress schema errors (42S02 = table missing, 42S22 = column missing)
        $isSchema = $e instanceof \PDOException
            && in_array($e->getCode(), ['42S02', '42S22'], true);
        if (!$isSchema) {
            throw $e;
        }
        $rows = [];
    }
    json_ok($rows);
} elseif ($method === 'PUT') {
    // Mark all as seen, or specific ID
    $id = $_GET['id'] ?? null;
    try {
        if ($id) {
            db_exec("UPDATE notifications SET seen_at = NOW() WHERE id = ? AND user_id = ?", [$id, $uid]);
        } else {
            db_exec("UPDATE notifications SET seen_at = NOW() WHERE user_id = ? AND seen_at IS NULL", [$uid]);
        }
    } catch (\Throwable $e) {
        // 42S02 = table not found, 42S22 = column not found (seen_at not yet migrated)
        $isSchema = $e instanceof \PDOException
            && in_array($e->getCode(), ['42S02', '42S22'], true);
        if (!$isSchema) {
            throw $e;
        }
    }
    json_ok(['ok' => true]);
} else {
    json_err(405, 'Methode nicht erlaubt.');
}
