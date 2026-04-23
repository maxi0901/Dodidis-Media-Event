<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$allowedEvents = ['page_view', 'calendar_click'];
$eventType = isset($_GET['event']) ? trim((string) $_GET['event']) : '';
$slug = 'gutshof-maifest-2026';
$source = isset($_GET['utm_source']) ? trim((string) $_GET['utm_source']) : '';
$campaign = isset($_GET['utm_campaign']) ? trim((string) $_GET['utm_campaign']) : '';

if (!in_array($eventType, $allowedEvents, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiges Event.']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$salt = getenv('TRACKING_SALT') ?: 'gutshof-maifest-2026-salt';
$ipHash = hash('sha256', $salt . '|' . $ip);
$userAgentHash = hash('sha256', $salt . '|' . $userAgent);

$dbPath = __DIR__ . '/tracking.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS landingpage_tracking (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landingpage_slug TEXT NOT NULL,
            event_type TEXT NOT NULL,
            source TEXT,
            campaign TEXT,
            ip_hash TEXT NOT NULL,
            user_agent_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime("now"))
        )'
    );

    $stmt = $pdo->prepare(
        'INSERT INTO landingpage_tracking (
            landingpage_slug, event_type, source, campaign, ip_hash, user_agent_hash, created_at
        ) VALUES (
            :landingpage_slug, :event_type, :source, :campaign, :ip_hash, :user_agent_hash, datetime("now")
        )'
    );

    $stmt->execute([
        ':landingpage_slug' => $slug,
        ':event_type' => $eventType,
        ':source' => $source,
        ':campaign' => $campaign,
        ':ip_hash' => $ipHash,
        ':user_agent_hash' => $userAgentHash,
    ]);

    http_response_code(204);
    exit;
} catch (Throwable $e) {
    error_log('Tracking error: ' . $e->getMessage());
    http_response_code(204);
    exit;
}
