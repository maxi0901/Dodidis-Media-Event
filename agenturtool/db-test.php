<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/response.php';
require_once __DIR__ . '/includes/db.php';

$tables = [
    'users','user_roles','customers','projects','shoot_days',
    'todos','todo_assignees','todo_seen_by','vacations',
    'project_files','activity_log','app_config',
];

try {
    $pdo = db();
    $ping = (int)$pdo->query("SELECT 1")->fetchColumn();
    $counts = [];
    foreach ($tables as $t) {
        try {
            $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        } catch (Throwable $e) {
            $counts[$t] = 'MISSING (' . $e->getMessage() . ')';
        }
    }
    json_ok([
        'ping'      => $ping,
        'database'  => (require __DIR__ . '/config.php')['database'],
        'php'       => PHP_VERSION,
        'tables'    => $counts,
    ]);
} catch (Throwable $e) {
    json_err(500, $e->getMessage());
}
