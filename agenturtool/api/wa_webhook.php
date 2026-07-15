<?php
declare(strict_types=1);

/**
 * WhatsApp-Webhook (Meta Cloud API). Kein Login — Meta ruft das auf.
 *
 *   GET  : Verifizierung (hub.challenge zurückgeben, wenn Verify-Token stimmt)
 *   POST : eingehende Nachrichten + Statusupdates → wa_conversations/wa_messages
 *
 * Signaturprüfung via X-Hub-Signature-256 (HMAC-SHA256 mit Meta-App-Secret).
 * Antwortet Meta immer schnell mit 200, damit nicht erneut zugestellt wird.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/meta_env.php';

$cfg = meta_config();

// ── GET — Webhook-Verifizierung ──────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $mode      = (string)($_GET['hub_mode'] ?? '');
    $token     = (string)($_GET['hub_verify_token'] ?? '');
    $challenge = (string)($_GET['hub_challenge'] ?? '');
    if ($mode === 'subscribe' && $token !== '' && hash_equals((string)$cfg['wa_verify_token'], $token)) {
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }
    http_response_code(403);
    exit('Forbidden');
}

// ── POST — Ereignisse ────────────────────────────────────────────────────────
$raw    = (string)file_get_contents('php://input');
$secret = (string)$cfg['app_secret'];

// Signatur prüfen (wenn App-Secret gesetzt). Header: sha256=<hmac>
$sigHeader = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
if ($secret !== '' && $sigHeader !== '') {
    $expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
    if (!hash_equals($expected, $sigHeader)) {
        http_response_code(403);
        exit('Bad signature');
    }
}

$data = json_decode($raw, true);
if (!is_array($data)) { http_response_code(200); exit('ok'); }

/** Vorschau-/Platzhaltertext je Nachrichtentyp. */
$previewOf = static function (array $m): string {
    $type = (string)($m['type'] ?? 'text');
    if ($type === 'text')     return (string)($m['text']['body'] ?? '');
    if ($type === 'image')    return '📷 Bild';
    if ($type === 'video')    return '🎬 Video';
    if ($type === 'audio')    return '🎧 Sprachnachricht';
    if ($type === 'document') return '📄 Dokument';
    if ($type === 'sticker')  return 'Sticker';
    if ($type === 'location') return '📍 Standort';
    return '[' . $type . ']';
};

foreach (($data['entry'] ?? []) as $entry) {
    foreach (($entry['changes'] ?? []) as $change) {
        $value = $change['value'] ?? [];
        if (!is_array($value)) continue;

        // Profilnamen je wa_id merken
        $names = [];
        foreach (($value['contacts'] ?? []) as $c) {
            $wid = (string)($c['wa_id'] ?? '');
            if ($wid !== '') $names[$wid] = (string)($c['profile']['name'] ?? '');
        }

        // Eingehende Nachrichten
        foreach (($value['messages'] ?? []) as $m) {
            $waId  = (string)($m['from'] ?? '');
            $wamid = (string)($m['id'] ?? '');
            if ($waId === '') continue;
            $preview = $previewOf($m);
            $ts = isset($m['timestamp']) ? date('Y-m-d H:i:s', (int)$m['timestamp']) : date('Y-m-d H:i:s');

            try {
                // Konversation anlegen/aktualisieren
                $conv = db_one("SELECT id FROM wa_conversations WHERE wa_id = ?", [$waId]);
                if ($conv) {
                    $convId = (string)$conv['id'];
                    db_exec(
                        "UPDATE wa_conversations
                            SET name = COALESCE(NULLIF(?, ''), name),
                                last_message_at = ?, last_preview = ?, last_direction = 'in',
                                unread = unread + 1
                          WHERE id = ?",
                        [$names[$waId] ?? '', $ts, mb_substr($preview, 0, 255), $convId]
                    );
                } else {
                    $convId = uid('wac');
                    db_exec(
                        "INSERT INTO wa_conversations (id, wa_id, name, last_message_at, last_preview, last_direction, unread)
                         VALUES (?, ?, ?, ?, ?, 'in', 1)",
                        [$convId, $waId, $names[$waId] ?? null, $ts, mb_substr($preview, 0, 255)]
                    );
                }

                // Nachricht speichern (dedupe über wa_message_id)
                db_exec(
                    "INSERT IGNORE INTO wa_messages
                       (id, conversation_id, wa_message_id, direction, type, body, status, wa_timestamp)
                     VALUES (?, ?, ?, 'in', ?, ?, 'received', ?)",
                    [uid('wam'), $convId, $wamid ?: null, (string)($m['type'] ?? 'text'), $preview, $ts]
                );
            } catch (\Throwable $e) {
                error_log('[wa_webhook] message: ' . $e->getMessage());
            }
        }

        // Statusupdates ausgehender Nachrichten (sent/delivered/read/failed)
        foreach (($value['statuses'] ?? []) as $s) {
            $wamid  = (string)($s['id'] ?? '');
            $status = (string)($s['status'] ?? '');
            if ($wamid === '' || $status === '') continue;
            try {
                db_exec("UPDATE wa_messages SET status = ? WHERE wa_message_id = ?", [$status, $wamid]);
            } catch (\Throwable $e) {
                error_log('[wa_webhook] status: ' . $e->getMessage());
            }
        }
    }
}

http_response_code(200);
echo 'ok';
