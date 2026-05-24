<?php
/**
 * Agenturtool – Legacy-Snapshot-API gegen normalisiertes Schema.
 *
 * Frontend-Verträge bleiben kompatibel zu der Bestands-SPA:
 *   GET  ?action=ping         – Health-Check
 *   GET  ?action=pull         – Komplettes Daten-Snapshot zurückgeben (Document-Form)
 *   POST ?action=push         – Upserts + Deletions auf normalisierte Tabellen mappen
 *
 * Sicherheit:
 *   - Session erforderlich (über includes/auth-check.php)
 *   - CSRF-Token bei push (X-CSRF-Token-Header)
 *   - Backend ist Auth-Authority; password_hash/pin_hash werden NIE im pull zurückgeliefert
 *
 * Mapping-Strategie:
 *   pull  → liest normalisierte Tabellen + Join-Tabellen, baut Document zusammen
 *   push  → empfängt {upserts: {table: [{id, data}]}}, mapped JSON-Felder auf Spalten
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/response.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

$session = require_login();
$action  = $_GET['action'] ?? '';

// ----------------------------------------------------------------------------
// Helfer: Mapping-Logik
// ----------------------------------------------------------------------------

function ensure_staff_or_customer_self(string $entity, ?string $id, array $session): void
{
    if (($session['type'] ?? '') !== 'customer') return;
    // Kunden dürfen nichts schreiben/aktualisieren
    json_err(403, 'Keine Berechtigung.');
}

function map_user_doc(array $u): array
{
    $roles = $u['roles'] ?? [];
    return [
        'id'         => (string)$u['id'],
        'username'   => (string)($u['username'] ?? ''),
        'name'       => (string)($u['name'] ?? ''),
        'email'      => $u['email'] ?? '',
        // password niemals zurückliefern!
        'roles'      => $roles,
        'avatarColor'=> $u['avatar_color'] ?? null,
        'avatarImage'=> $u['avatar_image'] ?? null,
        'calendarPrefs' => $u['calendar_prefs'] ? json_decode($u['calendar_prefs'], true) : null,
    ];
}

function customer_to_doc(array $c): array
{
    $doc = [
        'id'               => (string)$c['id'],
        'name'             => (string)$c['name'],
        'customerNumber'   => (string)$c['customer_number'],
        'managerId'        => $c['manager_id'],
        'email'            => $c['email']            ?? '',
        'phone'            => $c['phone']            ?? '',
        'contactName'      => $c['contact_name']     ?? '',
        'socialInstagram'  => $c['social_instagram'] ?? '',
        'socialTiktok'     => $c['social_tiktok']    ?? '',
        'notes'            => $c['notes']            ?? '',
        'addressStreet'    => $c['address_street']   ?? '',
        'addressZip'       => $c['address_zip']      ?? '',
        'addressCity'      => $c['address_city']     ?? '',
        'billingSameAsAddress' => (bool)$c['billing_same_as_address'],
        'billingStreet'    => $c['billing_street'] ?? '',
        'billingZip'       => $c['billing_zip']    ?? '',
        'billingCity'      => $c['billing_city']   ?? '',
        'vatId'            => $c['vat_id']         ?? '',
        'billingEmail'     => $c['billing_email']  ?? '',
        'contractStart'    => $c['contract_start'],
        'package'          => $c['package_name']   ?? ($c['package'] ?? ''),
        'monthlyRate'      => $c['monthly_rate'],
        'videosPerMonth'   => $c['videos_per_month'] !== null ? (int)$c['videos_per_month'] : '',
        'status'           => $c['status'],
        'checklist'        => [
            'contractSigned'  => (bool)($c['contract_signed']  ?? 0),
            'depositReceived' => (bool)($c['deposit_received'] ?? 0),
            'kickoffDone'     => (bool)($c['kickoff_done']     ?? 0),
            'socialAccess'    => (bool)($c['social_access']    ?? 0),
            'firstShoot'      => (bool)($c['first_shoot']      ?? 0),
        ],
        'createdAt'        => $c['created_at'],
    ];
    // pin_hash wird nicht raus geliefert (Sicherheit)
    return $doc;
}

function project_to_doc(array $p): array
{
    return [
        'id'           => (string)$p['id'],
        'title'        => (string)$p['title'],
        'customerId'   => $p['customer_id'],
        'videografId'  => $p['videograf_id'],
        'cutterId'     => $p['cutter_id'],
        'shootDate'    => $p['shoot_date'],
        'shootDayId'   => $p['shoot_day_id'],
        'deadline'     => $p['deadline'],
        'postingDate'  => $p['posting_date'],
        'script'       => $p['script'],
        'status'       => $p['status'],
        'isInternal'   => (bool)$p['is_internal'],
        'approvedAt'   => $p['approved_at'],
        'createdAt'    => $p['created_at'],
    ];
}

function vacation_to_doc(array $v): array
{
    return [
        'id'        => (string)$v['id'],
        'userId'    => $v['user_id'],
        'startDate' => $v['start_date'],
        'endDate'   => $v['end_date'],
        'note'      => $v['note'] ?? '',
        'approvedBy'=> $v['approved_by'],
        'approvedAt'=> $v['approved_at'],
    ];
}

function shootday_to_doc(array $s): array
{
    return [
        'id'              => (string)$s['id'],
        'date'            => $s['date'],
        'startTime'       => $s['start_time'],
        'endTime'         => $s['end_time'],
        'videografId'     => $s['videograf_id'],
        'customerId'      => $s['customer_id'],
        'note'            => $s['note'] ?? '',
        'rescheduledFrom' => $s['rescheduled_from'] ?? null,
        'createdAt'       => $s['created_at'],
    ];
}

function todo_to_doc(array $t, array $assigneeMap, array $seenMap): array
{
    return [
        'id'          => (string)$t['id'],
        'title'       => (string)$t['title'],
        'description' => $t['description'] ?? '',
        'createdById' => $t['created_by_id'] ?? $t['created_by'] ?? null,
        'dueDate'     => $t['due_date'],
        'status'      => $t['status'],
        'createdAt'   => $t['created_at'],
        'assigneeIds' => $assigneeMap[$t['id']] ?? [],
        'seenBy'      => $seenMap[$t['id']]     ?? [],
    ];
}

// ----------------------------------------------------------------------------
// PING
// ----------------------------------------------------------------------------
if ($action === 'ping') {
    json_ok(['ping' => 1, 'session_type' => $session['type']]);
}

// ----------------------------------------------------------------------------
// PULL – Document-Snapshot aus normalisierten Tabellen
// ----------------------------------------------------------------------------
if ($action === 'pull') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_err(405, 'GET erwartet.');

    $pdo  = db();
    $type = $session['type'] ?? 'staff';

    // Users (ohne password_hash)
    $nameCol = users_name_column();
    $userRows = db_all(
        "SELECT id, username, {$nameCol} AS name, email, avatar_color, avatar_image, calendar_prefs FROM users"
    );
    $rolesRows = db_all("SELECT user_id, role_name FROM user_roles");
    $rolesByUser = [];
    foreach ($rolesRows as $r) { $rolesByUser[$r['user_id']][] = $r['role_name']; }
    $users = [];
    foreach ($userRows as $u) {
        $u['roles'] = $rolesByUser[$u['id']] ?? [];
        $users[] = map_user_doc($u);
    }

    // Customers – mit LEFT JOIN auf customer_checklists
    $custSql = "SELECT c.id, c.name, c.customer_number, c.manager_id,
                       c.email, c.phone, c.contact_name, c.social_instagram, c.social_tiktok,
                       c.notes, c.address_street, c.address_zip, c.address_city,
                       c.billing_same_as_address, c.billing_street, c.billing_zip, c.billing_city,
                       c.vat_id, c.billing_email, c.contract_start, c.package_name,
                       c.monthly_rate, c.videos_per_month, c.status,
                       COALESCE(cl.contract_signed, 0)  AS contract_signed,
                       COALESCE(cl.deposit_received, 0) AS deposit_received,
                       COALESCE(cl.kickoff_done, 0)     AS kickoff_done,
                       COALESCE(cl.social_access, 0)    AS social_access,
                       COALESCE(cl.first_shoot, 0)      AS first_shoot,
                       c.created_at
                  FROM customers c
                  LEFT JOIN customer_checklists cl ON cl.customer_id = c.id";
    if ($type === 'customer') {
        $custRows = db_all("$custSql WHERE c.id = ?", [$session['cid']]);
    } else {
        $custRows = db_all($custSql);
    }
    $customers = array_map('customer_to_doc', $custRows);

    // Projects
    if ($type === 'customer') {
        $projRows = db_all("SELECT * FROM projects WHERE customer_id = ?", [$session['cid']]);
    } else {
        $projRows = db_all("SELECT * FROM projects");
    }
    $projects = array_map('project_to_doc', $projRows);

    // Vacations (volle Transparenz für Staff, leer für Customer)
    $vacations = $type === 'staff' ? array_map('vacation_to_doc', db_all("SELECT * FROM vacations")) : [];

    // ShootDays
    $shootDays = $type === 'staff' ? array_map('shootday_to_doc', db_all("SELECT * FROM shoot_days")) : [];

    // Todos + Assignees + SeenBy
    $todoRows = $type === 'staff' ? db_all("SELECT * FROM todos") : [];
    $assigneeMap = [];
    $seenMap     = [];
    if ($todoRows) {
        $ids = array_column($todoRows, 'id');
        $place = implode(',', array_fill(0, count($ids), '?'));
        foreach (db_all("SELECT todo_id, user_id FROM todo_assignees WHERE todo_id IN ($place)", $ids) as $r) {
            $assigneeMap[$r['todo_id']][] = $r['user_id'];
        }
        foreach (db_all("SELECT todo_id, user_id FROM todo_seen WHERE todo_id IN ($place)", $ids) as $r) {
            $seenMap[$r['todo_id']][] = $r['user_id'];
        }
    }
    $todos = array_map(fn($t) => todo_to_doc($t, $assigneeMap, $seenMap), $todoRows);

    // App-Config
    $appCfg = [];
    try {
        foreach (db_all("SELECT `key`, `value` FROM app_config") as $r) {
            $appCfg[] = ['key' => $r['key'], 'value' => json_decode($r['value'], true)];
        }
    } catch (\Throwable $e) {
        error_log('[api.php pull] app_config query failed: ' . $e->getMessage());
    }

    // Customer Files
    $customerFiles = [];
    try {
        $cfSql = $type === 'customer'
            ? ["SELECT id, customer_id, kind, filename, mime, size, path, uploaded_by, uploaded_at FROM customer_files WHERE customer_id = ? ORDER BY uploaded_at DESC", [$session['cid']]]
            : ["SELECT id, customer_id, kind, filename, mime, size, path, uploaded_by, uploaded_at FROM customer_files ORDER BY customer_id, uploaded_at DESC", []];
        foreach (db_all($cfSql[0], $cfSql[1]) as $f) {
            $customerFiles[] = [
                'id'         => (int)$f['id'],
                'customerId' => $f['customer_id'],
                'kind'       => $f['kind'],
                'filename'   => $f['filename'],
                'mime'       => $f['mime'],
                'size'       => (int)$f['size'],
                'path'       => $f['path'],
                'uploadedBy' => $f['uploaded_by'],
                'uploadedAt' => $f['uploaded_at'],
            ];
        }
    } catch (\Throwable $e) {
        error_log('[api.php pull] customer_files query failed: ' . $e->getMessage());
    }

    // Antwort: kompatibel zum Bestand (ok/data)
    echo json_encode([
        'ok'      => true,
        'success' => true,
        'data'    => [
            'users'          => $users,
            'customers'      => $customers,
            'projects'       => $projects,
            'vacations'      => $vacations,
            'todos'          => $todos,
            'shoot_days'     => $shootDays,
            'app_config'     => $appCfg,
            'customer_files' => $customerFiles,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ----------------------------------------------------------------------------
// PUSH – Document-Upserts auf normalisierte Spalten mappen
// ----------------------------------------------------------------------------
if ($action === 'push') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err(405, 'POST erwartet.');
    require_csrf();

    if (($session['type'] ?? '') === 'customer') {
        // Kunden dürfen mit der alten Bulk-Schnittstelle nichts ändern
        json_err(403, 'Keine Berechtigung.');
    }

    $body      = input_json();
    $upserts   = is_array($body['upserts'] ?? null)   ? $body['upserts']   : [];
    $deletions = is_array($body['deletions'] ?? null) ? $body['deletions'] : [];

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $isAdmin   = has_role('admin');
        $isManager = has_role('admin', 'manager');

        // ---------- USERS ----------
        if (!empty($upserts['users']) && is_array($upserts['users'])) {
            if (!$isAdmin) {
                // Nicht-Admins dürfen über das Bulk-API keine User editieren –
                // außer ihr eigenes Profil. Wir filtern entsprechend.
                $upserts['users'] = array_values(array_filter(
                    $upserts['users'],
                    fn($u) => isset($u['id'], $u['data']) && $u['id'] === $session['uid']
                ));
            }
            $nameCol = users_name_column();
            $stmtU = $pdo->prepare(
                "INSERT INTO users (id, username, {$nameCol}, email, password_hash, avatar_color, avatar_image, calendar_prefs)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    username       = VALUES(username),
                    {$nameCol}     = VALUES({$nameCol}),
                    email          = VALUES(email),
                    password_hash  = IF(VALUES(password_hash) = '', password_hash, VALUES(password_hash)),
                    avatar_color   = VALUES(avatar_color),
                    avatar_image   = VALUES(avatar_image),
                    calendar_prefs = VALUES(calendar_prefs)"
            );
            $stmtRoleDel = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmtRoleIns = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role_name) VALUES (?, ?)");

            foreach ($upserts['users'] as $u) {
                if (!isset($u['id'], $u['data']) || !is_array($u['data'])) continue;
                $d = $u['data'];
                $hash = (string)($d['password'] ?? '');
                // Nur sha256$-Hashes akzeptieren – Klartext bei Bulk-Push abweisen
                if ($hash !== '' && !str_starts_with($hash, 'sha256$')) {
                    $hash = '';
                }
                $stmtU->execute([
                    (string)$u['id'],
                    (string)($d['username'] ?? ''),
                    (string)($d['name'] ?? ''),
                    !empty($d['email']) ? (string)$d['email'] : null,
                    $hash,
                    !empty($d['avatarColor']) ? (string)$d['avatarColor'] : null,
                    $d['avatarImage'] ?? null,
                    isset($d['calendarPrefs']) ? json_encode($d['calendarPrefs'], JSON_UNESCAPED_UNICODE) : null,
                ]);
                if ($isAdmin) {
                    $stmtRoleDel->execute([(string)$u['id']]);
                    foreach ((array)($d['roles'] ?? []) as $r) {
                        if (in_array($r, ['admin','manager','videograf','cutter','mitarbeiter'], true)) {
                            $stmtRoleIns->execute([(string)$u['id'], $r]);
                        }
                    }
                }
            }
        }

        // ---------- CUSTOMERS ----------
        if (!empty($upserts['customers']) && is_array($upserts['customers'])) {
            if (!$isManager) json_err(403, 'Keine Berechtigung.');
            $stmtC = $pdo->prepare(
                "INSERT INTO customers (
                    id, name, customer_number, manager_id, pin_hash,
                    email, phone, contact_name, social_instagram, social_tiktok, notes,
                    address_street, address_zip, address_city,
                    billing_same_as_address, billing_street, billing_zip, billing_city,
                    vat_id, billing_email, contract_start, package_name, monthly_rate, videos_per_month, status
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    name = VALUES(name), customer_number = VALUES(customer_number), manager_id = VALUES(manager_id),
                    pin_hash = IF(VALUES(pin_hash) IS NULL, pin_hash, VALUES(pin_hash)),
                    email = VALUES(email), phone = VALUES(phone), contact_name = VALUES(contact_name),
                    social_instagram = VALUES(social_instagram), social_tiktok = VALUES(social_tiktok),
                    notes = VALUES(notes),
                    address_street = VALUES(address_street), address_zip = VALUES(address_zip), address_city = VALUES(address_city),
                    billing_same_as_address = VALUES(billing_same_as_address),
                    billing_street = VALUES(billing_street), billing_zip = VALUES(billing_zip), billing_city = VALUES(billing_city),
                    vat_id = VALUES(vat_id), billing_email = VALUES(billing_email),
                    contract_start = VALUES(contract_start), package_name = VALUES(package_name),
                    monthly_rate = VALUES(monthly_rate), videos_per_month = VALUES(videos_per_month),
                    status = VALUES(status)"
            );
            $stmtCL = $pdo->prepare(
                "INSERT INTO customer_checklists
                    (customer_id, contract_signed, deposit_received, kickoff_done, social_access, first_shoot)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    contract_signed  = VALUES(contract_signed),
                    deposit_received = VALUES(deposit_received),
                    kickoff_done     = VALUES(kickoff_done),
                    social_access    = VALUES(social_access),
                    first_shoot      = VALUES(first_shoot)"
            );
            foreach ($upserts['customers'] as $c) {
                if (!isset($c['id'], $c['data']) || !is_array($c['data'])) continue;
                $d = $c['data'];
                $cl = $d['checklist'] ?? [];
                $pinH = !empty($d['pinHash']) && str_starts_with((string)$d['pinHash'], 'sha256$')
                        ? (string)$d['pinHash'] : null;
                $stmtC->execute([
                    (string)$c['id'],
                    (string)($d['name'] ?? ''),
                    (string)($d['customerNumber'] ?? ''),
                    !empty($d['managerId']) ? (string)$d['managerId'] : null,
                    $pinH,
                    !empty($d['email']) ? (string)$d['email'] : null,
                    !empty($d['phone']) ? (string)$d['phone'] : null,
                    !empty($d['contactName']) ? (string)$d['contactName'] : null,
                    !empty($d['socialInstagram']) ? (string)$d['socialInstagram'] : null,
                    !empty($d['socialTiktok']) ? (string)$d['socialTiktok'] : null,
                    !empty($d['notes']) ? (string)$d['notes'] : null,
                    !empty($d['addressStreet']) ? (string)$d['addressStreet'] : null,
                    !empty($d['addressZip']) ? (string)$d['addressZip'] : null,
                    !empty($d['addressCity']) ? (string)$d['addressCity'] : null,
                    !empty($d['billingSameAsAddress']) ? 1 : 0,
                    !empty($d['billingStreet']) ? (string)$d['billingStreet'] : null,
                    !empty($d['billingZip']) ? (string)$d['billingZip'] : null,
                    !empty($d['billingCity']) ? (string)$d['billingCity'] : null,
                    !empty($d['vatId']) ? (string)$d['vatId'] : null,
                    !empty($d['billingEmail']) ? (string)$d['billingEmail'] : null,
                    as_date($d['contractStart'] ?? null),
                    !empty($d['package']) ? (string)$d['package'] : null,
                    isset($d['monthlyRate']) && $d['monthlyRate'] !== '' ? (string)$d['monthlyRate'] : null,
                    isset($d['videosPerMonth']) && $d['videosPerMonth'] !== '' ? (int)$d['videosPerMonth'] : null,
                    in_array($d['status'] ?? null, ['onboarding','active','paused'], true) ? $d['status'] : null,
                ]);
                // Checkliste in separate Tabelle
                try {
                    $stmtCL->execute([
                        (string)$c['id'],
                        !empty($cl['contractSigned'])  ? 1 : 0,
                        !empty($cl['depositReceived']) ? 1 : 0,
                        !empty($cl['kickoffDone'])     ? 1 : 0,
                        !empty($cl['socialAccess'])    ? 1 : 0,
                        !empty($cl['firstShoot'])      ? 1 : 0,
                    ]);
                } catch (Throwable $e) {
                    // Wenn customer_checklists nicht existiert, silent fail
                    error_log('[api.php/push/checklist] ' . $e->getMessage());
                }
            }
        }

        // ---------- SHOOT_DAYS ----------
        if (!empty($upserts['shoot_days']) && is_array($upserts['shoot_days'])) {
            if (!$isManager) json_err(403, 'Keine Berechtigung.');
            $stmtSD = $pdo->prepare(
                "INSERT INTO shoot_days (id, date, start_time, end_time, videograf_id, customer_id, note, rescheduled_from)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    rescheduled_from = IF(date <> VALUES(date), date, rescheduled_from),
                    date = VALUES(date), start_time = VALUES(start_time), end_time = VALUES(end_time),
                    videograf_id = VALUES(videograf_id), customer_id = VALUES(customer_id), note = VALUES(note)"
            );
            foreach ($upserts['shoot_days'] as $s) {
                if (!isset($s['id'], $s['data']) || !is_array($s['data'])) continue;
                $d = $s['data'];
                $newDate   = as_date($d['date'] ?? null) ?? '1970-01-01';
                $customerId = !empty($d['customerId']) ? (string)$d['customerId'] : null;

                // Datum-Änderung erkennen → Kundenbenachrichtigung eintragen
                $existing = db_one("SELECT date, customer_id FROM shoot_days WHERE id = ?", [(string)$s['id']]);
                if ($existing && $existing['date'] !== $newDate) {
                    $notifCustomerId = $customerId ?? $existing['customer_id'];
                    if ($notifCustomerId) {
                        $pdo->prepare(
                            "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type)
                             VALUES (?, 'shoot_day_rescheduled', 'Drehtag verschoben',
                                     CONCAT('Ihr Drehtag am ', ?, ' wurde auf ', ?, ' verschoben.'),
                                     ?, 'shootDay')"
                        )->execute([$notifCustomerId, $existing['date'], $newDate, (string)$s['id']]);
                    }
                }

                $stmtSD->execute([
                    (string)$s['id'],
                    $newDate,
                    as_time($d['startTime'] ?? null),
                    as_time($d['endTime']   ?? null),
                    !empty($d['videografId']) ? (string)$d['videografId'] : null,
                    $customerId,
                    !empty($d['note']) ? (string)$d['note'] : null,
                    !empty($d['rescheduledFrom']) ? (string)$d['rescheduledFrom'] : null,
                ]);
            }
        }

        // ---------- PROJECTS ----------
        if (!empty($upserts['projects']) && is_array($upserts['projects'])) {
            $stmtP = $pdo->prepare(
                "INSERT INTO projects (
                    id, title, customer_id, videograf_id, cutter_id,
                    shoot_date, shoot_day_id, deadline, posting_date, script,
                    status, is_internal, approved_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title), customer_id = VALUES(customer_id),
                    videograf_id = VALUES(videograf_id), cutter_id = VALUES(cutter_id),
                    shoot_date = VALUES(shoot_date), shoot_day_id = VALUES(shoot_day_id),
                    deadline = VALUES(deadline), posting_date = VALUES(posting_date),
                    script = VALUES(script), status = VALUES(status), is_internal = VALUES(is_internal),
                    approved_at = VALUES(approved_at)"
            );
            $validStatus = ['skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert'];

            foreach ($upserts['projects'] as $p) {
                if (!isset($p['id'], $p['data']) || !is_array($p['data'])) continue;
                $d = $p['data'];

                // Rolle: Videograf/Cutter dürfen nur eigene Projekte und nur status updaten.
                if (!$isManager) {
                    $cur = db_one(
                        "SELECT videograf_id, cutter_id, title, customer_id, shoot_date, shoot_day_id, deadline, posting_date, script, status, is_internal, approved_at
                           FROM projects WHERE id = ?",
                        [(string)$p['id']]
                    );
                    if (!$cur) continue;
                    if ($session['uid'] !== $cur['videograf_id'] && $session['uid'] !== $cur['cutter_id']) {
                        continue;
                    }
                    // Nur status erlauben
                    $d = array_merge([
                        'title' => $cur['title'],
                        'customerId'  => $cur['customer_id'],
                        'videografId' => $cur['videograf_id'],
                        'cutterId'    => $cur['cutter_id'],
                        'shootDate'   => $cur['shoot_date'],
                        'shootDayId'  => $cur['shoot_day_id'],
                        'deadline'    => $cur['deadline'],
                        'postingDate' => $cur['posting_date'],
                        'script'      => $cur['script'],
                        'isInternal'  => (bool)$cur['is_internal'],
                        'approvedAt'  => $cur['approved_at'],
                    ], array_intersect_key($d, ['status' => 1]));
                }

                $status = in_array($d['status'] ?? null, $validStatus, true) ? $d['status'] : 'skript';
                $approvedAt = $d['approvedAt'] ?? null;
                if ($status === 'freigegeben' && !$approvedAt) $approvedAt = date('Y-m-d H:i:s');

                $stmtP->execute([
                    (string)$p['id'],
                    (string)($d['title'] ?? ''),
                    !empty($d['customerId'])  ? (string)$d['customerId']  : null,
                    !empty($d['videografId']) ? (string)$d['videografId'] : null,
                    !empty($d['cutterId'])    ? (string)$d['cutterId']    : null,
                    as_date($d['shootDate']   ?? null),
                    !empty($d['shootDayId'])  ? (string)$d['shootDayId']  : null,
                    as_date($d['deadline']    ?? null),
                    as_date($d['postingDate'] ?? null),
                    $d['script'] ?? null,
                    $status,
                    !empty($d['isInternal']) ? 1 : 0,
                    $approvedAt ? date('Y-m-d H:i:s', strtotime($approvedAt)) : null,
                ]);
            }
        }

        // ---------- VACATIONS ----------
        if (!empty($upserts['vacations']) && is_array($upserts['vacations'])) {
            $stmtV = $pdo->prepare(
                "INSERT INTO vacations (id, user_id, start_date, end_date, note, approved_by, approved_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    user_id = VALUES(user_id), start_date = VALUES(start_date),
                    end_date = VALUES(end_date), note = VALUES(note),
                    approved_by = VALUES(approved_by), approved_at = VALUES(approved_at)"
            );
            foreach ($upserts['vacations'] as $v) {
                if (!isset($v['id'], $v['data']) || !is_array($v['data'])) continue;
                $d = $v['data'];
                // Self oder Admin
                if (!$isAdmin && ($d['userId'] ?? null) !== $session['uid']) continue;
                $stmtV->execute([
                    (string)$v['id'],
                    (string)($d['userId'] ?? $session['uid']),
                    as_date($d['startDate'] ?? null) ?? '1970-01-01',
                    as_date($d['endDate']   ?? null) ?? '1970-01-01',
                    !empty($d['note']) ? (string)$d['note'] : null,
                    !empty($d['approvedBy']) ? (string)$d['approvedBy'] : null,
                    !empty($d['approvedAt']) ? date('Y-m-d H:i:s', strtotime($d['approvedAt'])) : null,
                ]);
            }
        }

        // ---------- TODOS ----------
        if (!empty($upserts['todos']) && is_array($upserts['todos'])) {
            $stmtT  = $pdo->prepare(
                "INSERT INTO todos (id, title, description, created_by_id, due_date, status)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title), description = VALUES(description),
                    created_by_id = VALUES(created_by_id), due_date = VALUES(due_date), status = VALUES(status)"
            );
            $stmtAD = $pdo->prepare("DELETE FROM todo_assignees WHERE todo_id = ?");
            $stmtAI = $pdo->prepare("INSERT IGNORE INTO todo_assignees (todo_id, user_id) VALUES (?, ?)");
            $stmtSD2= $pdo->prepare("DELETE FROM todo_seen WHERE todo_id = ?");
            $stmtSI = $pdo->prepare("INSERT IGNORE INTO todo_seen (todo_id, user_id) VALUES (?, ?)");

            foreach ($upserts['todos'] as $t) {
                if (!isset($t['id'], $t['data']) || !is_array($t['data'])) continue;
                $d = $t['data'];

                // Rolle: non-manager dürfen nur status+seenBy ändern
                if (!$isManager) {
                    $cur = db_one("SELECT title, description, created_by_id, due_date FROM todos WHERE id = ?", [(string)$t['id']]);
                    if (!$cur) continue;
                    $isAssignee = (bool)db_one(
                        "SELECT 1 AS ok FROM todo_assignees WHERE todo_id = ? AND user_id = ?",
                        [(string)$t['id'], $session['uid']]
                    );
                    if (!$isAssignee && $cur['created_by_id'] !== $session['uid']) continue;
                    $d = [
                        'title'       => $cur['title'],
                        'description' => $cur['description'],
                        'createdById' => $cur['created_by_id'],
                        'dueDate'     => $cur['due_date'],
                        'status'      => in_array($d['status'] ?? null, ['open','done'], true) ? $d['status'] : 'open',
                        'seenBy'      => $d['seenBy'] ?? null,
                    ];
                }

                $stmtT->execute([
                    (string)$t['id'],
                    (string)($d['title'] ?? ''),
                    $d['description'] ?? null,
                    !empty($d['createdById']) ? (string)$d['createdById'] : ($session['uid'] ?? null),
                    as_date($d['dueDate'] ?? null),
                    in_array($d['status'] ?? null, ['open','done'], true) ? $d['status'] : 'open',
                ]);

                if ($isManager && array_key_exists('assigneeIds', $d)) {
                    $stmtAD->execute([(string)$t['id']]);
                    foreach ((array)$d['assigneeIds'] as $uid) {
                        if ($uid) $stmtAI->execute([(string)$t['id'], (string)$uid]);
                    }
                }
                if (array_key_exists('seenBy', $d) && $d['seenBy'] !== null) {
                    $stmtSD2->execute([(string)$t['id']]);
                    foreach ((array)$d['seenBy'] as $uid) {
                        if ($uid) $stmtSI->execute([(string)$t['id'], (string)$uid]);
                    }
                }
            }
        }

        // ---------- APP_CONFIG ----------
        if (!empty($upserts['app_config']) && is_array($upserts['app_config'])) {
            $stmtAC = $pdo->prepare(
                "INSERT INTO app_config (`key`, `value`) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
            );
            foreach ($upserts['app_config'] as $row) {
                if (!isset($row['key'])) continue;
                $stmtAC->execute([
                    (string)$row['key'],
                    json_encode($row['value'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        }

        // ---------- DELETIONS ----------
        $delTables = ['users','customers','projects','vacations','todos','shoot_days'];
        foreach ($delTables as $t) {
            $ids = $deletions[$t] ?? [];
            if (!is_array($ids) || empty($ids)) continue;
            $ids = array_values(array_map('strval', $ids));

            // Berechtigung:
            //   users        → admin
            //   customers    → admin
            //   projects     → admin/manager
            //   shoot_days   → admin/manager
            //   todos        → admin/manager
            //   vacations    → self/admin
            if ($t === 'users' || $t === 'customers') {
                if (!$isAdmin) continue;
            } elseif (in_array($t, ['projects','shoot_days','todos'], true)) {
                if (!$isManager) continue;
            } elseif ($t === 'vacations') {
                if (!$isAdmin) {
                    // Nur eigene
                    $place = implode(',', array_fill(0, count($ids), '?'));
                    $own = db_all("SELECT id FROM vacations WHERE user_id = ? AND id IN ($place)",
                                  array_merge([$session['uid']], $ids));
                    $ids = array_column($own, 'id');
                    if (!$ids) continue;
                }
            }

            $place = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM `$t` WHERE id IN ($place)")->execute($ids);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        log_err('legacy api push failed', ['msg' => $e->getMessage()]);
        json_err(500, 'Push fehlgeschlagen.');
    }

    echo json_encode(['ok' => true, 'success' => true, 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

json_err(404, 'Unbekannte action. Erlaubt: ping, pull, push');
