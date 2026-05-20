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

$selectCols = "c.id, c.name, c.customer_number AS customerNumber, c.manager_id AS managerId,
               c.email, c.phone, c.contact_name AS contactName,
               c.social_instagram AS socialInstagram, c.social_tiktok AS socialTiktok,
               c.notes,
               c.address_street AS addressStreet, c.address_zip AS addressZip, c.address_city AS addressCity,
               c.billing_same_as_address AS billingSameAsAddress,
               c.billing_street AS billingStreet, c.billing_zip AS billingZip, c.billing_city AS billingCity,
               c.vat_id AS vatId, c.billing_email AS billingEmail,
               c.contract_start AS contractStart, c.package_name AS package, c.monthly_rate AS monthlyRate,
               c.videos_per_month AS videosPerMonth, c.status,
               COALESCE(cl.contract_signed, 0) AS contract_signed,
               COALESCE(cl.deposit_received, 0) AS deposit_received,
               COALESCE(cl.kickoff_done, 0) AS kickoff_done,
               COALESCE(cl.social_access, 0) AS social_access,
               COALESCE(cl.first_shoot, 0) AS first_shoot,
               c.created_at AS createdAt, c.updated_at AS updatedAt";
$fromCust = "FROM customers c LEFT JOIN customer_checklists cl ON cl.customer_id = c.id";

switch ($method) {

    case 'GET': {
        if ($id) {
            // Customer-Session darf nur den eigenen Datensatz sehen
            if (($session['type'] ?? '') === 'customer') {
                if ($session['cid'] !== $id) json_err(403, 'Keine Berechtigung.');
            } else {
                require_role('admin', 'manager');
            }
            $c = db_one("SELECT $selectCols $fromCust WHERE c.id = ?", [$id]);
            if (!$c) json_err(404, 'Kunde nicht gefunden.');
            $c['checklist'] = checklist_from_row($c);
            $c = strip_checklist_cols($c);
            json_ok(strip_secrets($c));
        }

        if (($session['type'] ?? '') === 'customer') {
            // Customer sieht NUR sich selbst in der Liste
            $c = db_one("SELECT $selectCols $fromCust WHERE c.id = ?", [$session['cid']]);
            if (!$c) json_ok([]);
            $c['checklist'] = checklist_from_row($c);
            $c = strip_checklist_cols($c);
            json_ok([strip_secrets($c)]);
        }

        require_role('admin', 'manager');
        $rows = db_all("SELECT $selectCols $fromCust ORDER BY c.customer_number");
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

        [$params] = build_customer_params($b);
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

        // Checklisten-Zeile anlegen (ignoriert falls schon vorhanden)
        try {
            db_exec("INSERT IGNORE INTO customer_checklists (customer_id) VALUES (?)", [$newId]);
        } catch (Throwable $e) {
            log_err('customer checklist init', ['msg' => $e->getMessage()]);
        }

        log_activity('customer', $newId, 'created');
        $row = db_one("SELECT $selectCols $fromCust WHERE c.id = ?", [$newId]);
        $row['checklist'] = checklist_from_row($row);
        json_ok(strip_secrets(strip_checklist_cols($row)), 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        require_role('admin', 'manager');
        $b = input_json();
        $existing = db_one("SELECT id FROM customers WHERE id = ?", [$id]);
        if (!$existing) json_err(404, 'Kunde nicht gefunden.');

        [$params, $checklistParams] = build_customer_params($b);
        if (!$params && !$checklistParams) json_ok(['id' => $id]);

        if ($params) {
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
        }

        // Checklist in separater Tabelle aktualisieren
        if ($checklistParams) {
            try {
                db_exec(
                    "INSERT INTO customer_checklists
                        (customer_id, contract_signed, deposit_received, kickoff_done, social_access, first_shoot)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        contract_signed  = VALUES(contract_signed),
                        deposit_received = VALUES(deposit_received),
                        kickoff_done     = VALUES(kickoff_done),
                        social_access    = VALUES(social_access),
                        first_shoot      = VALUES(first_shoot)",
                    [
                        $id,
                        $checklistParams['contract_signed'],
                        $checklistParams['deposit_received'],
                        $checklistParams['kickoff_done'],
                        $checklistParams['social_access'],
                        $checklistParams['first_shoot'],
                    ]
                );
            } catch (Throwable $e) {
                log_err('customer checklist update', ['msg' => $e->getMessage()]);
            }
        }

        log_activity('customer', $id, 'edited');
        $row = db_one("SELECT $selectCols $fromCust WHERE c.id = ?", [$id]);
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

/**
 * Gibt [customerParams, checklistParams] zurück.
 * checklistParams ist leer, wenn keine Checklisten-Felder geändert wurden.
 * Checklist-Felder gehen in die separate customer_checklists-Tabelle.
 */
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
        'package'           => ['package_name', 190],
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
    if (array_key_exists('pinHash', $b) && $b['pinHash']) {
        $h = (string)$b['pinHash'];
        if (str_starts_with($h, 'sha256$')) {
            $out['pin_hash'] = $h;
        }
    }

    // Checklist geht in separate Tabelle
    $checklist = [];
    if (array_key_exists('checklist', $b) && is_array($b['checklist'])) {
        $cl = $b['checklist'];
        $checklist = [
            'contract_signed'  => as_bool($cl['contractSigned']  ?? 0),
            'deposit_received' => as_bool($cl['depositReceived'] ?? 0),
            'kickoff_done'     => as_bool($cl['kickoffDone']     ?? 0),
            'social_access'    => as_bool($cl['socialAccess']    ?? 0),
            'first_shoot'      => as_bool($cl['firstShoot']      ?? 0),
        ];
    }

    return [$out, $checklist];
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
