<?php
declare(strict_types=1);

/**
 * POST /api/content_create.php
 * n8n legt einen neuen Content-Entwurf in der Queue an (Station 1: Erzeugung).
 * Der Entwurf erscheint danach im Agenturtool zur Prüfung/Freigabe.
 *
 * Body: {
 *   "media_url": "https://...",        // Pflicht
 *   "caption": "...",                  // optional
 *   "customer_id": "cust_ab12",        // optional (muss existieren, wenn gesetzt)
 *   "platform": "instagram",           // optional, default instagram
 *   "content_type": "story",           // optional, default story
 *   "scheduled_at": "2026-06-07 09:00" // optional Wunschzeit; final setzt Staff bei Freigabe
 * }
 * Auth: Header X-API-KEY.
 */

require_once __DIR__ . '/../includes/api_auth.php';

require_api_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    api_send(405, ['success' => false, 'error' => 'Method not allowed']);
}

$body = input_json();

$mediaUrl = trim((string)($body['media_url'] ?? ''));
if ($mediaUrl === '') {
    api_send(400, ['success' => false, 'error' => 'media_url required']);
}

$platform    = trim((string)($body['platform'] ?? 'instagram')) ?: 'instagram';
$contentType = trim((string)($body['content_type'] ?? 'story')) ?: 'story';
$caption     = isset($body['caption']) ? (string)$body['caption'] : null;

// Optionale Kundenzuordnung – wenn gesetzt, muss der Kunde existieren.
$customerId = null;
if (!empty($body['customer_id'])) {
    $customerId = (string)$body['customer_id'];
    $cust = db_one("SELECT id FROM customers WHERE id = ?", [$customerId]);
    if (!$cust) {
        api_send(400, ['success' => false, 'error' => 'Unknown customer_id']);
    }
}

// Optionale Wunschzeit (lockere Validierung über strtotime).
$scheduledAt = null;
if (!empty($body['scheduled_at'])) {
    $ts = strtotime((string)$body['scheduled_at']);
    if ($ts === false) {
        api_send(400, ['success' => false, 'error' => 'Invalid scheduled_at']);
    }
    $scheduledAt = date('Y-m-d H:i:s', $ts);
}

db_exec(
    "INSERT INTO content_queue (customer_id, platform, content_type, caption, media_url, status, scheduled_at)
     VALUES (?, ?, ?, ?, ?, 'draft', ?)",
    [$customerId, $platform, $contentType, $caption, $mediaUrl, $scheduledAt]
);

$newId = (int)db()->lastInsertId();

api_send(201, ['success' => true, 'id' => $newId, 'message' => 'Content created']);
