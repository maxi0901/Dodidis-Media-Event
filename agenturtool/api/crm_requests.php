<?php
declare(strict_types=1);

/**
 * CRM › Anfragen — Verwaltung der Kontaktformular-Anfragen. NUR ADMINS.
 *
 *   GET               — alle Anfragen (neueste zuerst)
 *   PUT    ?id=        — Status ändern (neu | bearbeitet | erledigt)
 *   DELETE ?id=        — Anfrage löschen
 *
 * Das öffentliche Anlegen läuft über contact_request.php (kein Login).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) require_csrf();

if (($session['type'] ?? '') === 'customer' || !has_role('admin')) {
    json_err(403, 'Nur Admins.');
}

$cols = "id, name, email, company, message, status, source,
         created_at AS createdAt, updated_at AS updatedAt";
$id = $_GET['id'] ?? null;
$allowedStatus = ['neu', 'bearbeitet', 'erledigt'];

switch ($method) {

    case 'GET': {
        $rows = db_all("SELECT $cols FROM crm_requests ORDER BY created_at DESC");
        json_ok($rows);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        $cur = db_one("SELECT id FROM crm_requests WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Anfrage nicht gefunden.');
        $b = input_json();
        $status = (string)($b['status'] ?? '');
        if (!in_array($status, $allowedStatus, true)) json_err(400, 'Ungültiger Status.');
        db_exec("UPDATE crm_requests SET status = ? WHERE id = ?", [$status, $id]);
        log_activity('crm_request', (string)$id, 'status', ['status' => $status]);
        json_ok(db_one("SELECT $cols FROM crm_requests WHERE id = ?", [$id]));
    }

    case 'DELETE': {
        if (!$id) json_err(400, 'id fehlt.');
        $cur = db_one("SELECT id FROM crm_requests WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Anfrage nicht gefunden.');
        db_exec("DELETE FROM crm_requests WHERE id = ?", [$id]);
        log_activity('crm_request', (string)$id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
