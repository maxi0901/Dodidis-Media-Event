<?php
declare(strict_types=1);

/**
 * Instagram-Automatisierung: Regeln verwalten + Ereignis-Log. Nur Admin.
 *
 *   GET  ?action=rules                 — alle Regeln (neueste zuerst)
 *   GET  ?action=events                — letzte 100 ausgelöste Ereignisse
 *   POST ?action=save   {…rule…}       — Regel anlegen/ändern
 *   POST ?action=toggle {id, enabled}  — Regel aktivieren/deaktivieren
 *   POST ?action=delete {id}           — Regel löschen
 *
 * Die Regeln arbeitet ig_webhook.php ab (Meta ruft ihn bei neuen Kommentaren).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';

if ($method === 'POST') require_csrf();
if (($session['type'] ?? '') !== 'staff' || !has_role('admin')) {
    json_err(403, 'Nur Admins dürfen die Automatisierung verwalten.');
}

const IG_MATCH_TYPES = ['contains', 'exact', 'any'];

// ── GET ?action=rules ────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'rules') {
    $rows = db_all(
        "SELECT r.id, r.customer_id AS customerId, c.name AS customerName,
                r.name, r.enabled, r.match_type AS matchType, r.keywords,
                r.reply_public AS replyPublic, r.reply_dm AS replyDm,
                r.priority, r.created_at AS createdAt
           FROM ig_rules r
      LEFT JOIN customers c ON c.id = r.customer_id
          ORDER BY r.priority ASC, r.created_at DESC"
    );
    foreach ($rows as &$r) { $r['enabled'] = (int)$r['enabled']; $r['priority'] = (int)$r['priority']; }
    json_ok($rows);
}

// ── GET ?action=events ───────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'events') {
    $rows = db_all(
        "SELECT e.id, e.comment_id AS commentId, e.customer_id AS customerId, c.name AS customerName,
                e.rule_id AS ruleId, r.name AS ruleName, e.from_username AS fromUsername,
                e.text, e.did_public AS didPublic, e.did_dm AS didDm, e.status, e.detail,
                e.created_at AS createdAt
           FROM ig_events e
      LEFT JOIN customers c ON c.id = e.customer_id
      LEFT JOIN ig_rules  r ON r.id = e.rule_id
          ORDER BY e.created_at DESC
          LIMIT 100"
    );
    foreach ($rows as &$r) { $r['didPublic'] = (int)$r['didPublic']; $r['didDm'] = (int)$r['didDm']; }
    json_ok($rows);
}

// ── POST ?action=save ────────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'save') {
    $b = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($b)) $b = [];

    $id         = trim((string)($b['id'] ?? ''));
    $customerId = trim((string)($b['customer_id'] ?? ''));
    $name       = trim((string)($b['name'] ?? ''));
    $matchType  = trim((string)($b['match_type'] ?? 'contains'));
    $keywords   = trim((string)($b['keywords'] ?? ''));
    $replyPub   = trim((string)($b['reply_public'] ?? ''));
    $replyDm    = trim((string)($b['reply_dm'] ?? ''));
    $priority   = (int)($b['priority'] ?? 0);
    $enabled    = !empty($b['enabled']) ? 1 : 0;

    if ($name === '') json_err(400, 'Name ist Pflicht.');
    if (!in_array($matchType, IG_MATCH_TYPES, true)) json_err(400, 'Ungültiger Match-Typ.');
    if ($matchType !== 'any' && $keywords === '') json_err(400, 'Bitte mindestens ein Schlagwort angeben.');
    if ($replyPub === '' && $replyDm === '') json_err(400, 'Mindestens eine Aktion (öffentliche Antwort oder DM) festlegen.');

    // Kunde optional (leer = gilt für ALLE verbundenen Konten). Wenn gesetzt, muss er existieren.
    if ($customerId !== '') {
        $c = db_one("SELECT id FROM customers WHERE id = ?", [$customerId]);
        if (!$c) json_err(404, 'Kunde nicht gefunden.');
    }

    if ($id === '') {
        $id = uid('igr');
        db_exec(
            "INSERT INTO ig_rules
               (id, customer_id, name, enabled, match_type, keywords, reply_public, reply_dm, priority, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$id, $customerId ?: null, $name, $enabled, $matchType, $keywords ?: null,
             $replyPub ?: null, $replyDm ?: null, $priority, $session['uid']]
        );
        log_activity('ig_rule', $id, 'created', ['name' => $name]);
    } else {
        $exists = db_one("SELECT id FROM ig_rules WHERE id = ?", [$id]);
        if (!$exists) json_err(404, 'Regel nicht gefunden.');
        db_exec(
            "UPDATE ig_rules
                SET customer_id=?, name=?, enabled=?, match_type=?, keywords=?,
                    reply_public=?, reply_dm=?, priority=?
              WHERE id=?",
            [$customerId ?: null, $name, $enabled, $matchType, $keywords ?: null,
             $replyPub ?: null, $replyDm ?: null, $priority, $id]
        );
        log_activity('ig_rule', $id, 'updated', ['name' => $name]);
    }
    json_ok(['id' => $id]);
}

// ── POST ?action=toggle ──────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'toggle') {
    $b  = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($b)) $b = [];
    $id = trim((string)($b['id'] ?? ''));
    if ($id === '') json_err(400, 'id ist Pflicht.');
    $en = !empty($b['enabled']) ? 1 : 0;
    if (!db_one("SELECT id FROM ig_rules WHERE id = ?", [$id])) json_err(404, 'Regel nicht gefunden.');
    db_exec("UPDATE ig_rules SET enabled = ? WHERE id = ?", [$en, $id]);
    json_ok(['id' => $id, 'enabled' => $en]);
}

// ── POST ?action=delete ──────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    $b  = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($b)) $b = [];
    $id = trim((string)($b['id'] ?? ''));
    if ($id === '') json_err(400, 'id ist Pflicht.');
    db_exec("DELETE FROM ig_rules WHERE id = ?", [$id]);
    log_activity('ig_rule', $id, 'deleted');
    json_ok(['id' => $id]);
}

json_err(400, 'Unbekannte action.');
