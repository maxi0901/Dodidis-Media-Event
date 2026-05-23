<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
if ($session['type'] === 'customer') json_err(403, 'Keine Berechtigung.');
if (!has_role('admin','manager','contract_uploader')) json_err(403,'Keine Berechtigung.');

$method = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['POST','DELETE'], true)) require_csrf();

$contractId = $_GET['contractId'] ?? null;

if ($method === 'POST') {
    if (!$contractId) json_err(400, 'contractId fehlt.');
    $c = db_one("SELECT id FROM contracts WHERE id = ?", [$contractId]);
    if (!$c) json_err(404, 'Vertrag nicht gefunden.');

    $b = input_json();
    $text = trim($b['text'] ?? '');
    $newId = uid('cc');

    db_exec(
        "INSERT INTO contract_comments (id, contract_id, user_id, comment_text) VALUES (?, ?, ?, ?)",
        [$newId, $contractId, $session['uid'], $text ?: null]
    );
    log_activity('contract', $contractId, 'commented');

    // Notify contract uploader (if different from commenter)
    $contract = db_one("SELECT uploaded_by, title FROM contracts WHERE id = ?", [$contractId]);
    if ($contract && $contract['uploaded_by'] !== $session['uid']) {
        db_exec(
            "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type) VALUES (?, 'contract_comment', ?, ?, ?, 'contract')",
            [$contract['uploaded_by'], 'Neuer Kommentar', $contract['title'], $contractId]
        );
    }

    $row = db_one(
        "SELECT id, user_id AS userId, comment_text AS text, voice_path AS voicePath,
                voice_filename AS voiceFilename, created_at AS createdAt
           FROM contract_comments WHERE id = ?",
        [$newId]
    );
    json_ok($row, 201);

} elseif ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) json_err(400, 'id fehlt.');
    require_role('admin');
    db_exec("DELETE FROM contract_comments WHERE id = ?", [$id]);
    json_ok(['id' => $id]);
} else {
    json_err(405, 'Methode nicht erlaubt.');
}
