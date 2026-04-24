<?php
declare(strict_types=1);

$allowedEvents = ['page_view', 'calendar_click'];
$landingpageSlug = 'gutshof-maifest-2026';
$dataDir = __DIR__ . '/data';
$dataFile = $dataDir . '/tracking.json';
$salt = 'gutshof-maifest-2026-local-salt-v1';

$rawBody = file_get_contents('php://input');
$bodyData = [];
if (is_string($rawBody) && $rawBody !== '') {
    $decodedBody = json_decode($rawBody, true);
    if (is_array($decodedBody)) {
        $bodyData = $decodedBody;
    }
}

$eventType = (string)($_GET['event'] ?? $bodyData['event'] ?? '');
$source = (string)($_GET['source'] ?? $_GET['utm_source'] ?? $bodyData['source'] ?? '');
$campaign = (string)($_GET['campaign'] ?? $_GET['utm_campaign'] ?? $bodyData['campaign'] ?? '');

$eventType = trim($eventType);
$source = trim($source);
$campaign = trim($campaign);

if (!in_array($eventType, $allowedEvents, true)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Ungültiges Event.']);
    exit;
}

$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

$record = [
    'landingpage_slug' => $landingpageSlug,
    'event_type' => $eventType,
    'source' => $source,
    'campaign' => $campaign,
    'created_at' => gmdate('c'),
    'ip_hash' => hash('sha256', $salt . '|' . $ip),
    'user_agent_hash' => hash('sha256', $salt . '|' . $userAgent),
];

try {
    if (!is_dir($dataDir) && !mkdir($dataDir, 0755, true) && !is_dir($dataDir)) {
        throw new RuntimeException('Data-Verzeichnis konnte nicht erstellt werden.');
    }

    if (!file_exists($dataFile) && file_put_contents($dataFile, "[]\n", LOCK_EX) === false) {
        throw new RuntimeException('tracking.json konnte nicht erstellt werden.');
    }

    $handle = fopen($dataFile, 'c+');
    if ($handle === false) {
        throw new RuntimeException('tracking.json konnte nicht geöffnet werden.');
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            throw new RuntimeException('Datei-Lock für tracking.json fehlgeschlagen.');
        }

        rewind($handle);
        $existingContent = stream_get_contents($handle);
        $decoded = json_decode($existingContent !== false ? $existingContent : '[]', true);
        $events = is_array($decoded) ? $decoded : [];

        $events[] = $record;

        $encoded = json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new RuntimeException('JSON-Encoding fehlgeschlagen.');
        }

        ftruncate($handle, 0);
        rewind($handle);
        if (fwrite($handle, $encoded . PHP_EOL) === false) {
            throw new RuntimeException('tracking.json konnte nicht geschrieben werden.');
        }

        fflush($handle);
        flock($handle, LOCK_UN);
    } finally {
        fclose($handle);
    }

    http_response_code(204);
    exit;
} catch (Throwable $exception) {
    error_log('Tracking error: ' . $exception->getMessage());
    http_response_code(204);
    exit;
}
