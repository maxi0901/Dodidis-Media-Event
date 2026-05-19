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

$selectCols = "id, name, customer_number AS customerNumber, manager_id AS managerId,
               email, phone, contact_name AS contactName,
               social_instagram AS socialInstagram, social_tiktok AS socialTiktok,
               notes,
               address_street AS addressStreet, address_zip AS addressZip, address_city AS addressCity,
               billing_same_as_address AS billingSameAsAddress,
               billing_street AS billingStreet, billing_zip AS billingZip, billing_city AS billingCity,
               vat_id AS vatId, billing_email AS billingEmail,
               contract_start AS contractStart, package, monthly_rate AS monthlyRate,
               videos_per_month AS videosPerMonth, status,
               contract_signed, deposit_received, kickoff_done, social_access, first_shoot,
               created_at AS createdAt, updated_at AS updatedAt";

switch ($method) {

    case 'GET': {
        if ($id) {
            // Customer-Session darf nur den eigenen Datensatz sehen
            if (($session['type'] ?? '') === 'customer') {
                if ($session['cid'] !== $id) json_err(403, 'Keine Berechtigung.');
            } else {
                require_role('admin', 'manager');
            }
            $c = db_one("SELECT $selectCols FROM customers WHERE id = ?", [$id]);
            if (!$c) json_err(404, 'Kunde nicht gefunden.');
            $c['checklist'] = checklist_from_row($c);
            $c = strip_checklist_cols($c);
            json_ok(strip_secrets($c));
        }

        if (($session['type'] ?? '') === 'customer') {
            // Customer sieht NUR sich selbst in der Liste
            $c = db_one("SELECT $selectCols FROM customers WHERE id = ?", [$session['cid']]);
            if (!$c) json_ok([]);
            $c['checklist'] = checklist_from_row($c);
            $c = strip_checklist_cols($c);
            json_ok([strip_secrets($c)]);
        }

        require_role('admin', 'manager');
        $rows = db_all("SELECT $selectCols FROM customers ORDER BY customer_number");
        foreach ($rows as &$r) {
            $r['checklist'] = checklist_from_row($r);
            $r = strip_checklist_cols($r);
        }
        unset($r);
        json_ok($rows);
    }

    case 'POST': {
        require_role('admin', 'manager');
        $b = input_json();
        $name = s($b['name'] ?? null, 190);
        $num  = s($b['customerNumber'] ?? null, 32);
        if (!$name || !$num) json_err(400, 'name und customerNumber sind Pflicht.');
        $newId = $b['id'] ?? uid('c');

        $params = build_customer_params($b);
        $params['id']              = $newId;
        $params['name']            = $name;
        $params['customer_number'] = $num;

        $cols = array_keys($params);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO customers (" . implode(',', array_map(fn($c) => "`$c`", $cols)) . ") VALUES ($placeholders)";

        try {
            db_exec($sql, array_values($params));
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'uk_customers_number')) {
                json_err(409, 'Kundennummer bereits vergeben.');
            }
            log_err('customer create', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte Kunde nicht anlegen.');
        }

        log_activity('customer', $newId, 'created');
        $row = db_one("SELECT $selectCols FROM customers WHERE id = ?", [$newId]);
        $row['checklist'] = checklist_from_row($row);
        json_ok(strip_secrets(strip_checklist_cols($row)), 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        require_role('admin', 'manager');
        $b = input_json();
        $existing = db_one("SELECT id FROM customers WHERE id = ?", [$id]);
        if (!$existing) json_err(404, 'Kunde nicht gefunden.');

        $params = build_customer_params($b);
        if (!$params) json_ok(['id' => $id]);

        $set  = [];
        $vals = [];
        foreach ($params as $col => $val) {
            $set[]  = "`$col` = ?";
            $vals[] = $val;
        }
        $vals[] = $id;

        try {
            db_exec("UPDATE customers SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'uk_customers_number')) {
                json_err(409, 'Kundennummer bereits vergeben.');
            }
            log_err('customer update', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte Kunde nicht speichern.');
        }

        log_activity('customer', $id, 'edited');
        $row = db_one("SELECT $selectCols FROM customers WHERE id = ?", [$id]);
        $row['checklist'] = checklist_from_row($row);
        json_ok(strip_secrets(strip_checklist_cols($row)));
    }

    case 'DELETE': {
        require_role('admin');
        if (!$id) json_err(400, 'id fehlt.');
        db_exec("DELETE FROM customers WHERE id = ?", [$id]);
        log_activity('customer', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}

// ---------------------------------------------------------------------------

function build_customer_params(array $b): array
{
    $map = [
        'name'              => ['name', 190],
        'customerNumber'    => ['customer_number', 32],
        'managerId'         => ['manager_id', 64],
        'email'             => ['email', 190],
        'phone'             => ['phone', 64],
        'contactName'       => ['contact_name', 190],
        'socialInstagram'   => ['social_instagram', 190],
        'socialTiktok'      => ['social_tiktok', 190],
        'notes'             => ['notes', 65000],
        'addressStreet'     => ['address_street', 190],
        'addressZip'        => ['address_zip', 16],
        'addressCity'       => ['address_city', 96],
        'billingStreet'     => ['billing_street', 190],
        'billingZip'        => ['billing_zip', 16],
        'billingCity'       => ['billing_city', 96],
        'vatId'             => ['vat_id', 64],
        'billingEmail'      => ['billing_email', 190],
        'package'           => ['package', 190],
    ];
    $out = [];
    foreach ($map as $k => [$col, $max]) {
        if (array_key_exists($k, $b)) {
            $out[$col] = s((string)($b[$k] ?? ''), $max);
        }
    }

    if (array_key_exists('billingSameAsAddress', $b)) {
        $out['billing_same_as_address'] = as_bool($b['billingSameAsAddress']);
    }
    if (array_key_exists('contractStart', $b)) {
        $out['contract_start'] = as_date((string)$b['contractStart']);
    }
    if (array_key_exists('monthlyRate', $b)) {
        $out['monthly_rate'] = as_dec($b['monthlyRate']);
    }
    if (array_key_exists('videosPerMonth', $b)) {
        $out['videos_per_month'] = as_int($b['videosPerMonth']);
    }
    if (array_key_exists('status', $b)) {
        $st = $b['status'];
        $out['status'] = in_array($st, ['onboarding','active','paused'], true) ? $st : null;
    }
    if (array_key_exists('checklist', $b) && is_array($b['checklist'])) {
        $cl = $b['checklist'];
        $out['contract_signed']  = as_bool($cl['contractSigned']  ?? 0);
        $out['deposit_received'] = as_bool($cl['depositReceived'] ?? 0);
        $out['kickoff_done']     = as_bool($cl['kickoffDone']     ?? 0);
        $out['social_access']    = as_bool($cl['socialAccess']    ?? 0);
        $out['first_shoot']      = as_bool($cl['firstShoot']      ?? 0);
    }
    if (array_key_exists('pinHash', $b) && $b['pinHash']) {
        $h = (string)$b['pinHash'];
        if (str_starts_with($h, 'sha256$')) {
            $out['pin_hash'] = $h;
        }
    }
    return $out;
}

function checklist_from_row(array $row): array
{
    return [
        'contractSigned'  => (bool)($row['contract_signed']  ?? 0),
        'depositReceived' => (bool)($row['deposit_received'] ?? 0),
        'kickoffDone'     => (bool)($row['kickoff_done']     ?? 0),
        'socialAccess'    => (bool)($row['social_access']    ?? 0),
        'firstShoot'      => (bool)($row['first_shoot']      ?? 0),
    ];
}

function strip_checklist_cols(array $row): array
{
    unset(
        $row['contract_signed'],
        $row['deposit_received'],
        $row['kickoff_done'],
        $row['social_access'],
        $row['first_shoot']
    );
    return $row;
}
