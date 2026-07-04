<?php
declare(strict_types=1);

/**
 * Meta-OAuth-Callback (Redirect-Ziel der Meta-App) + Seiten-Auswahl.
 *
 *  GET  ?code=&state=   — von Meta aufgerufen: Token tauschen, Seiten holen.
 *                         Eine Seite → direkt verbinden. Mehrere → Auswahl.
 *  POST ?action=finalize — gewählte Seite speichern (server-gerendertes Formular).
 *
 * Speichert je Kunde eine facebook-Zeile (Page-Token) und – falls vorhanden –
 * eine instagram-Zeile (IG-Business-Account, nutzt denselben Page-Token) in
 * social_accounts. Access-Tokens werden NIE an den Client zurückgegeben.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/MetaClient.php';

$session = require_login();
if (($session['type'] ?? '') === 'customer' || !has_role('admin', 'manager')) {
    json_err(403, 'Nur Manager oder Admins dürfen Konten verbinden.');
}

$appBase = app_base_url();

// ── POST ?action=finalize — gewählte Seite speichern ─────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_GET['action'] ?? '') === 'finalize') {
    // Manueller CSRF-Check (server-gerendertes Formular sendet Token als Feld)
    $token = (string)($_POST['csrf'] ?? '');
    if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], $token)) {
        finish_html(403, 'Sitzung abgelaufen', 'Bitte den Verbinden-Vorgang neu starten.', $appBase);
    }
    $cand = $_SESSION['meta_connect'] ?? null;
    if (!$cand || empty($cand['pages'])) {
        finish_html(400, 'Nichts zu verbinden', 'Keine Auswahl im Zwischenspeicher. Bitte neu starten.', $appBase);
    }
    $chosen = (string)($_POST['page_id'] ?? '');
    $page = null;
    foreach ($cand['pages'] as $p) { if ($p['id'] === $chosen) { $page = $p; break; } }
    if (!$page) {
        finish_html(400, 'Ungültige Auswahl', 'Die gewählte Seite ist nicht mehr verfügbar.', $appBase);
    }

    store_accounts((string)$cand['customer_id'], $page, $cand['token_expires_at'] ?? null);
    unset($_SESSION['meta_connect']);
    finish_html(200, 'Konto verbunden ✓',
        'Die Seite „' . htmlspecialchars($page['name']) . '" wurde verbunden'
        . ($page['ig_username'] ? ' (Instagram: @' . htmlspecialchars($page['ig_username']) . ')' : '') . '.',
        $appBase);
}

// ── GET-Callback von Meta ────────────────────────────────────────────────────
if (!empty($_GET['error'])) {
    finish_html(400, 'Verbindung abgebrochen',
        'Meta meldete: ' . htmlspecialchars((string)($_GET['error_description'] ?? $_GET['error'])), $appBase);
}

$code  = (string)($_GET['code'] ?? '');
$state = (string)($_GET['state'] ?? '');
$saved = $_SESSION['meta_oauth'] ?? null;

if ($code === '' || $state === '' || !$saved
    || !hash_equals((string)($saved['state'] ?? ''), $state)) {
    finish_html(400, 'Ungültige Rückmeldung', 'State-Prüfung fehlgeschlagen. Bitte neu starten.', $appBase);
}
$customerId  = (string)$saved['customer_id'];
$redirectUri = (string)$saved['redirect_uri'];
unset($_SESSION['meta_oauth']);

try {
    $meta       = new MetaClient($redirectUri);
    $shortTok   = $meta->exchangeCode($code);
    $longLived  = $meta->longLivedToken($shortTok);
    $pages      = $meta->fetchPages($longLived['token']);
} catch (\Throwable $e) {
    finish_html(502, 'Meta-Fehler', htmlspecialchars($e->getMessage()), $appBase);
}

if (!$pages) {
    finish_html(400, 'Keine Seite gefunden',
        'Mit diesem Konto ist keine Facebook-Seite verbunden. Für Instagram-Posting muss ein '
        . 'Instagram-Business-/Creator-Account mit einer Facebook-Seite verknüpft sein.', $appBase);
}

// Genau eine Seite → direkt verbinden
if (count($pages) === 1) {
    store_accounts($customerId, $pages[0], $longLived['expires_at']);
    finish_html(200, 'Konto verbunden ✓',
        'Die Seite „' . htmlspecialchars($pages[0]['name']) . '" wurde verbunden'
        . ($pages[0]['ig_username'] ? ' (Instagram: @' . htmlspecialchars($pages[0]['ig_username']) . ')' : '') . '.',
        $appBase);
}

// Mehrere Seiten → Auswahl anzeigen (Kandidaten inkl. Tokens serverseitig zwischenspeichern)
$_SESSION['meta_connect'] = [
    'customer_id'      => $customerId,
    'token_expires_at' => $longLived['expires_at'],
    'pages'            => $pages,
];
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
render_picker($pages, (string)$_SESSION['csrf']);

// ─────────────────────────────────────────────────────────────────────────────

function app_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x'))), '/');
    return $scheme . '://' . $host . $path . '/';
}

/**
 * Speichert facebook- (+ ggf. instagram-)Zeile für den Kunden.
 * Upsert über UNIQUE(customer_id, platform).
 */
function store_accounts(string $customerId, array $page, ?string $expiresAt): void
{
    $pageToken = (string)$page['access_token'];

    upsert_account($customerId, 'facebook', [
        'label'    => (string)$page['name'],
        'external' => (string)$page['id'],
        'page_id'  => (string)$page['id'],
        'token'    => $pageToken,
        'expires'  => $expiresAt,
    ]);

    if (!empty($page['ig_id'])) {
        upsert_account($customerId, 'instagram', [
            'label'    => $page['ig_username'] ? '@' . $page['ig_username'] : (string)$page['ig_id'],
            'external' => (string)$page['ig_id'],
            'page_id'  => (string)$page['id'],
            'token'    => $pageToken, // IG-Publishing nutzt das Page-Token
            'expires'  => $expiresAt,
        ]);
    }

    log_activity('social_account', $customerId, 'connected',
        ['page' => $page['id'], 'ig' => $page['ig_id'] ?? null]);
}

function upsert_account(string $customerId, string $platform, array $d): void
{
    $existing = db_one(
        "SELECT id FROM social_accounts WHERE customer_id = ? AND platform = ?",
        [$customerId, $platform]
    );
    if ($existing) {
        db_exec(
            "UPDATE social_accounts
                SET account_label=?, external_id=?, page_id=?, access_token=?,
                    token_expires_at=?, status='connected', updated_at=NOW()
              WHERE id=?",
            [$d['label'], $d['external'], $d['page_id'], $d['token'], $d['expires'], $existing['id']]
        );
    } else {
        db_exec(
            "INSERT INTO social_accounts
               (id, customer_id, platform, account_label, external_id, page_id,
                access_token, token_expires_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'connected')",
            [uid('sa'), $customerId, $platform, $d['label'], $d['external'],
             $d['page_id'], $d['token'], $d['expires']]
        );
    }
}

function render_picker(array $pages, string $csrf): void
{
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    $rows = '';
    foreach ($pages as $i => $p) {
        $ig = $p['ig_username'] ? ' · IG @' . htmlspecialchars($p['ig_username']) : ' · kein Instagram verknüpft';
        $rows .= '<label style="display:flex;gap:10px;align-items:center;padding:12px;border:1px solid #333;border-radius:10px;margin-bottom:8px;cursor:pointer">'
              . '<input type="radio" name="page_id" value="' . htmlspecialchars($p['id']) . '"' . ($i === 0 ? ' checked' : '') . '>'
              . '<span><b>' . htmlspecialchars($p['name']) . '</b><br><span style="color:#888;font-size:12px">' . $ig . '</span></span>'
              . '</label>';
    }
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<body style="font-family:system-ui;background:#0f0f0f;color:#e5e5e5;max-width:560px;margin:40px auto;padding:0 20px">'
       . '<h1 style="font-size:20px">Seite auswählen</h1>'
       . '<p style="color:#888;font-size:14px">Dieses Konto verwaltet mehrere Seiten. Wähle die Seite, die mit diesem Kunden verbunden werden soll.</p>'
       . '<form method="post" action="?action=finalize">'
       . '<input type="hidden" name="csrf" value="' . htmlspecialchars($csrf) . '">'
       . $rows
       . '<button type="submit" style="margin-top:12px;padding:11px 22px;border:none;border-radius:10px;background:#0a84ff;color:#fff;font-weight:700;font-size:14px;cursor:pointer">Verbinden</button>'
       . '</form></body>';
    exit;
}

function finish_html(int $code, string $title, string $msg, string $appBase): void
{
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    $ok = $code < 400;
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<body style="font-family:system-ui;background:#0f0f0f;color:#e5e5e5;max-width:520px;margin:60px auto;padding:0 20px;text-align:center">'
       . '<div style="font-size:40px">' . ($ok ? '✅' : '⚠️') . '</div>'
       . '<h1 style="font-size:20px">' . $title . '</h1>'
       . '<p style="color:#aaa;font-size:14px;line-height:1.5">' . $msg . '</p>'
       . '<a href="' . htmlspecialchars($appBase) . '" style="display:inline-block;margin-top:20px;padding:11px 22px;border-radius:10px;background:#0a84ff;color:#fff;text-decoration:none;font-weight:700;font-size:14px">Zurück zur App</a>'
       . '</body>';
    exit;
}
