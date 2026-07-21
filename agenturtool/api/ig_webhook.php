<?php
declare(strict_types=1);

/**
 * Instagram-Webhook (Meta Graph API) — Auto-Antworten/DMs auf Kommentare.
 * Kein Login — Meta ruft das auf. Ersetzt ManyChat für einfache Regeln.
 *
 *   GET  : Verifizierung (hub.challenge zurückgeben, wenn Verify-Token stimmt)
 *   POST : eingehende Kommentar-Ereignisse (field="comments") → Regel-Engine
 *
 * Ablauf pro Kommentar:
 *   1. Dedupe über comment_id (ein Event pro Kommentar; Meta-Retries ignoriert).
 *   2. Eigene Kommentare (vom Konto selbst) werden übersprungen (keine Schleife).
 *   3. Konto → Kunde + Token aus social_accounts (external_id = IG-Konto-ID).
 *   4. Erste passende, aktive Regel (Kunde ODER global) wird angewandt:
 *      öffentliche Antwort und/oder private DM.
 *   5. Ergebnis wird in ig_events protokolliert.
 *
 * Signaturprüfung via X-Hub-Signature-256 (HMAC-SHA256 mit Meta-App-Secret).
 * Antwortet Meta immer schnell mit 200, damit nicht erneut zugestellt wird.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/meta_env.php';
require_once __DIR__ . '/MetaClient.php';

$cfg = meta_config();

// ── GET — Webhook-Verifizierung ──────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $mode      = (string)($_GET['hub_mode'] ?? '');
    $token     = (string)($_GET['hub_verify_token'] ?? '');
    $challenge = (string)($_GET['hub_challenge'] ?? '');
    if ($mode === 'subscribe' && $token !== '' && hash_equals((string)$cfg['ig_verify_token'], $token)) {
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }
    http_response_code(403);
    exit('Forbidden');
}

// ── POST — Ereignisse ────────────────────────────────────────────────────────
$raw    = (string)file_get_contents('php://input');
// Instagram-Webhooks (Instagram-Business-Login) sind mit dem SEPARATEN
// Instagram-App-Geheimcode signiert. Falls gesetzt, den nehmen — sonst als
// Fallback der Facebook-app_secret (alte Graph-API-Anbindung).
$secret = (string)(($cfg['ig_app_secret'] ?? '') !== '' ? $cfg['ig_app_secret'] : $cfg['app_secret']);

// Signatur PFLICHT, sobald ein App-Secret gesetzt ist (Endpunkt hat kein Login).
$sigHeader = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
// Diagnose: jeder eingehende POST wird protokolliert (kein Secret/Payload).
error_log('[ig_webhook] POST empfangen len=' . strlen($raw)
    . ' sig=' . ($sigHeader !== '' ? '1' : '0')
    . ' app_secret=' . ($secret !== '' ? 'gesetzt' : 'LEER'));

// Letzten Webhook-Versuch für die Admin-Diagnose (meta_diag.php) festhalten —
// single-key upsert in app_config, kein Wachstum, keine Secrets.
$recordLast = static function (int $status, string $note) use ($sigHeader, $secret) {
    try {
        db_exec(
            "INSERT INTO app_config (`key`, value) VALUES ('ig_webhook_last', ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)",
            [json_encode([
                'status'    => $status,
                'note'      => $note,
                'signatur'  => ($sigHeader !== '' ? 'vorhanden' : 'fehlt'),
                'appSecret' => ($secret !== '' ? 'gesetzt' : 'LEER'),
                'zeit'      => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE)]
        );
    } catch (\Throwable $e) { /* Diagnose ist best-effort */ }
};

if ($secret !== '') {
    if ($sigHeader === '') {
        error_log('[ig_webhook] ABGEWIESEN: Signatur fehlt');
        $recordLast(403, 'Signatur fehlt');
        http_response_code(403); exit('Missing signature');
    }
    $expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
    if (!hash_equals($expected, $sigHeader)) {
        // Genaue Diagnose: gegen BEIDE hinterlegten Codes prüfen, damit die
        // Admin-Diagnose zeigt, WELCHER Code (falls überhaupt) zur empfangenen
        // Signatur passt. HMAC-Präfixe sind keine Secrets → anzeigbar.
        $fbSecret = (string)($cfg['app_secret'] ?? '');
        $igSecret = (string)($cfg['ig_app_secret'] ?? '');
        $fbSig    = $fbSecret !== '' ? 'sha256=' . hash_hmac('sha256', $raw, $fbSecret) : '';
        $igSig    = $igSecret !== '' ? 'sha256=' . hash_hmac('sha256', $raw, $igSecret) : '';

        $match = 'KEINEM hinterlegten Code';
        if ($igSig !== '' && hash_equals($igSig, $sigHeader)) {
            $match = 'Instagram-App-Geheimcode (ig_app_secret)';
        } elseif ($fbSig !== '' && hash_equals($fbSig, $sigHeader)) {
            $match = 'Facebook-App-Geheimcode (app_secret)';
        }
        $used = $igSecret !== '' ? 'ig_app_secret' : 'app_secret (Fallback)';

        $note = 'Signatur ungültig bei X-Hub-Signature-256. '
            . 'Empfangen ' . (substr($sigHeader, 0, 20) ?: '—') . '…'
            . ' · geprüft mit ' . $used
            . ' · Signatur passt zu: ' . $match
            . ' · Body ' . strlen($raw) . ' B';

        error_log('[ig_webhook] ABGEWIESEN: ' . $note);
        $recordLast(403, $note);
        http_response_code(403); exit('Bad signature');
    }
}

$data = json_decode($raw, true);
if (!is_array($data)) { $recordLast(200, 'empfangen (leer/kein JSON)'); http_response_code(200); exit('ok'); }

/** Prüft, ob ein Kommentartext auf eine Regel passt. */
$ruleMatches = static function (array $rule, string $text): bool {
    $type = (string)($rule['match_type'] ?? 'contains');
    if ($type === 'any') return true;
    $hay = mb_strtolower(trim($text));
    $kws = preg_split('/[\r\n,]+/', (string)($rule['keywords'] ?? '')) ?: [];
    foreach ($kws as $kw) {
        $kw = mb_strtolower(trim($kw));
        if ($kw === '') continue;
        if ($type === 'exact'   && $hay === $kw)                 return true;
        if ($type === 'contains' && mb_strpos($hay, $kw) !== false) return true;
    }
    return false;
};

foreach (($data['entry'] ?? []) as $entry) {
    $igAccountId = (string)($entry['id'] ?? '');

    foreach (($entry['changes'] ?? []) as $change) {
        if (($change['field'] ?? '') !== 'comments') continue;
        $v = $change['value'] ?? [];
        if (!is_array($v)) continue;

        $commentId = (string)($v['id'] ?? '');
        if ($commentId === '') continue;
        $text     = (string)($v['text'] ?? '');
        $fromId   = (string)($v['from']['id'] ?? '');
        $fromUser = (string)($v['from']['username'] ?? '');
        $mediaId  = (string)($v['media']['id'] ?? '');

        try {
            // 1) Dedupe: nur wenn wirklich neu eingefügt, weitermachen.
            $created = db_exec(
                "INSERT IGNORE INTO ig_events
                   (id, comment_id, media_id, from_username, text, status)
                 VALUES (?, ?, ?, ?, ?, 'pending')",
                [uid('ige'), $commentId, $mediaId ?: null, $fromUser ?: null, $text]
            );
            if ($created < 1) continue; // Meta-Retry / Duplikat

            // Eigene Kommentare (Konto antwortet sich sonst selbst) SICHTBAR
            // überspringen — so sieht man im Log, dass der Webhook ankam.
            if ($fromId !== '' && $fromId === $igAccountId) {
                db_exec("UPDATE ig_events SET status='skipped', detail='Eigener Kommentar (kein Selbst-Antworten)' WHERE comment_id=?", [$commentId]);
                continue;
            }

            // 2) Konto → Kunde + Token
            $acc = db_one(
                "SELECT customer_id AS customerId, access_token AS token
                   FROM social_accounts
                  WHERE external_id = ? AND platform = 'instagram' AND status = 'connected'
                  LIMIT 1",
                [$igAccountId]
            );
            if (!$acc || empty($acc['token'])) {
                db_exec("UPDATE ig_events SET status='skipped', detail=? WHERE comment_id=?",
                    ['Kein verbundenes Instagram-Konto zu ' . $igAccountId, $commentId]);
                continue;
            }
            $customerId = (string)($acc['customerId'] ?? '');
            $token      = (string)$acc['token'];
            db_exec("UPDATE ig_events SET customer_id=? WHERE comment_id=?", [$customerId ?: null, $commentId]);

            // 3) Erste passende, aktive Regel. Gilt für den Kunden ODER global UND
            //    entweder für alle Beiträge (media_id NULL) oder genau diesen Beitrag.
            //    Beitrags-spezifische Regeln ranken vor kontoweiten.
            $rules = db_all(
                "SELECT id, match_type, keywords, reply_public, reply_dm
                   FROM ig_rules
                  WHERE enabled = 1
                    AND (customer_id = ? OR customer_id IS NULL)
                    AND (media_id IS NULL OR media_id = ?)
                  ORDER BY (media_id IS NULL) ASC, priority ASC, created_at ASC",
                [$customerId, $mediaId]
            );
            $hit = null;
            foreach ($rules as $r) { if ($ruleMatches($r, $text)) { $hit = $r; break; } }

            if (!$hit) {
                db_exec("UPDATE ig_events SET status='skipped', detail='Keine passende Regel' WHERE comment_id=?", [$commentId]);
                continue;
            }

            // 4) Aktionen ausführen
            $client   = new MetaClient('x');
            $didPub   = 0; $didDm = 0; $errs = [];
            $pub      = trim((string)($hit['reply_public'] ?? ''));
            $dm       = trim((string)($hit['reply_dm'] ?? ''));

            if ($pub !== '') {
                try { $client->replyToComment($commentId, $token, $pub); $didPub = 1; }
                catch (\Throwable $e) { $errs[] = 'Antwort: ' . $e->getMessage(); }
            }
            if ($dm !== '') {
                try { $client->sendInstagramPrivateReply($igAccountId, $token, $commentId, $dm); $didDm = 1; }
                catch (\Throwable $e) { $errs[] = 'DM: ' . $e->getMessage(); }
            }

            $status = $errs ? 'error' : 'ok';
            db_exec(
                "UPDATE ig_events SET rule_id=?, did_public=?, did_dm=?, status=?, detail=? WHERE comment_id=?",
                [(string)$hit['id'], $didPub, $didDm, $status, $errs ? implode(' | ', $errs) : null, $commentId]
            );
        } catch (\Throwable $e) {
            error_log('[ig_webhook] ' . $e->getMessage());
        }
    }
}

$recordLast(200, 'empfangen & verarbeitet');
http_response_code(200);
echo 'ok';
