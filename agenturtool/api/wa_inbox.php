<?php
declare(strict_types=1);

/**
 * WhatsApp-Team-Posteingang (Kommunikation). Nur Admin.
 *
 *   GET  ?action=conversations              — Chatliste (neueste zuerst)
 *   GET  ?action=messages&id=<convId>       — Verlauf eines Chats (setzt unread=0)
 *   POST ?action=send {id, text}            — Antwort senden (Graph-API)
 *
 * Eingehende Nachrichten liefert wa_webhook.php. Senden nur im 24-h-Fenster
 * frei formulierbar (sonst Meta-Fehler → wird durchgereicht).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/MetaClient.php';
require_once __DIR__ . '/meta_env.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';

if ($method === 'POST') require_csrf();
if (($session['type'] ?? '') !== 'staff' || !has_role('admin')) {
    json_err(403, 'Nur Admins haben Zugriff auf die Kommunikation.');
}

// ── GET ?action=conversations ────────────────────────────────────────────────
if ($method === 'GET' && $action === 'conversations') {
    $rows = db_all(
        "SELECT id, wa_id AS waId, name, last_preview AS lastPreview,
                last_message_at AS lastMessageAt, last_direction AS lastDirection, unread
           FROM wa_conversations
          ORDER BY (last_message_at IS NULL), last_message_at DESC
          LIMIT 200"
    );
    foreach ($rows as &$r) { $r['unread'] = (int)$r['unread']; }
    json_ok($rows);
}

// ── GET ?action=messages&id= ─────────────────────────────────────────────────
if ($method === 'GET' && $action === 'messages') {
    $convId = (string)($_GET['id'] ?? '');
    $conv = db_one("SELECT id, wa_id AS waId, name FROM wa_conversations WHERE id = ?", [$convId]);
    if (!$conv) json_err(404, 'Chat nicht gefunden.');

    $msgs = db_all(
        "SELECT id, direction, type, body, status, wa_timestamp AS waTimestamp,
                sent_by AS sentBy, created_at AS createdAt
           FROM wa_messages
          WHERE conversation_id = ?
          ORDER BY created_at ASC
          LIMIT 500",
        [$convId]
    );
    // Gelesen markieren
    try { db_exec("UPDATE wa_conversations SET unread = 0 WHERE id = ?", [$convId]); } catch (\Throwable $_) {}

    json_ok(['conversation' => $conv, 'messages' => $msgs]);
}

// ── POST ?action=send ────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'send') {
    $b      = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($b)) $b = [];
    $convId = (string)($b['id'] ?? '');
    $text   = trim((string)($b['text'] ?? ''));
    if ($convId === '') json_err(400, 'id ist Pflicht.');
    if ($text === '')   json_err(400, 'Nachricht ist leer.');

    $conv = db_one("SELECT id, wa_id AS waId FROM wa_conversations WHERE id = ?", [$convId]);
    if (!$conv) json_err(404, 'Chat nicht gefunden.');

    $cfg = meta_config();
    if ($cfg['wa_phone_number_id'] === '' || $cfg['wa_token'] === '') {
        json_err(500, 'WhatsApp ist nicht konfiguriert (wa_phone_number_id / wa_token fehlen).');
    }

    try {
        $res   = (new MetaClient('x'))->sendWhatsAppText(
            $cfg['wa_phone_number_id'], $cfg['wa_token'], (string)$conv['waId'], $text
        );
        $wamid = (string)($res['messages'][0]['id'] ?? '');
    } catch (\Throwable $e) {
        json_err(502, 'Senden fehlgeschlagen: ' . $e->getMessage());
    }

    $now = date('Y-m-d H:i:s');
    db_exec(
        "INSERT INTO wa_messages
           (id, conversation_id, wa_message_id, direction, type, body, status, wa_timestamp, sent_by)
         VALUES (?, ?, ?, 'out', 'text', ?, 'sent', ?, ?)",
        [uid('wam'), $convId, $wamid ?: null, $text, $now, $session['uid']]
    );
    db_exec(
        "UPDATE wa_conversations
            SET last_message_at = ?, last_preview = ?, last_direction = 'out', unread = 0
          WHERE id = ?",
        [$now, mb_substr($text, 0, 255), $convId]
    );
    log_activity('wa', $convId, 'replied', ['to' => $conv['waId']]);

    json_ok(['messageId' => $wamid]);
}

json_err(400, 'Unbekannte action.');
