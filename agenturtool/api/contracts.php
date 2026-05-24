<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = $_GET['id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

switch ($method) {

    case 'GET': {
        require_role('admin', 'manager', 'contract_uploader');
        if ($id) {
            $c = db_one(
                "SELECT id, title, customer_id AS customerId, status,
                        filename, mime, size, path,
                        created_at AS createdAt, updated_at AS updatedAt
                   FROM contracts WHERE id = ?",
                [$id]
            );
            if (!$c) json_err(404, 'Vertrag nicht gefunden.');
            json_ok($c);
        }
        $rows = db_all(
            "SELECT id, title, customer_id AS customerId, status,
                    filename, mime, size, path,
                    created_at AS createdAt, updated_at AS updatedAt
               FROM contracts
              ORDER BY created_at DESC"
        );
        json_ok($rows);
    }

    case 'POST': {
        if (!has_role('admin', 'manager', 'contract_uploader')) json_err(403, 'Keine Berechtigung.');
        $b = input_json();
        $title = s($b['title'] ?? null, 255);
        if (!$title) json_err(400, 'title ist Pflicht.');
        $customerId = s($b['customerId'] ?? null, 64);
        $newId = uid('ct');

        db_exec(
            "INSERT INTO contracts (id, title, customer_id, status, created_by)
             VALUES (?, ?, ?, 'draft', ?)",
            [$newId, $title, $customerId, $session['uid']]
        );

        log_activity('contract', $newId, 'created', ['title' => $title]);
        $row = db_one(
            "SELECT id, title, customer_id AS customerId, status,
                    filename, mime, size, path,
                    created_at AS createdAt, updated_at AS updatedAt
               FROM contracts WHERE id = ?",
            [$newId]
        );
        json_ok($row, 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        require_role('admin', 'manager');
        $b = input_json();
        $existing = db_one("SELECT id FROM contracts WHERE id = ?", [$id]);
        if (!$existing) json_err(404, 'Vertrag nicht gefunden.');

        $set  = [];
        $vals = [];

        if (array_key_exists('title', $b)) {
            $title = s($b['title'], 255);
            if (!$title) json_err(400, 'title darf nicht leer sein.');
            $set[]  = 'title = ?';
            $vals[] = $title;
        }
        if (array_key_exists('customerId', $b)) {
            $set[]  = 'customer_id = ?';
            $vals[] = s($b['customerId'], 64);
        }
        if (array_key_exists('status', $b)) {
            $st = $b['status'];
            if (!in_array($st, ['draft', 'confirmed'], true)) json_err(400, 'Ungültiger Status.');
            $set[]  = 'status = ?';
            $vals[] = $st;
        }

        if ($set) {
            $vals[] = $id;
            db_exec("UPDATE contracts SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        }

        log_activity('contract', $id, 'edited');
        $row = db_one(
            "SELECT id, title, customer_id AS customerId, status,
                    filename, mime, size, path,
                    created_at AS createdAt, updated_at AS updatedAt
               FROM contracts WHERE id = ?",
            [$id]
        );
        json_ok($row);
    }

    case 'DELETE': {
        require_role('admin');
        if (!$id) json_err(400, 'id fehlt.');
        $existing = db_one("SELECT id FROM contracts WHERE id = ?", [$id]);
        if (!$existing) json_err(404, 'Vertrag nicht gefunden.');
        db_exec("DELETE FROM contracts WHERE id = ?", [$id]);
        log_activity('contract', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
