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

$cols = "id, title, customer_id AS customerId, videograf_id AS videografId,
         cutter_id AS cutterId, shoot_date AS shootDate, shoot_day_id AS shootDayId,
         deadline, posting_date AS postingDate, script, status,
         is_internal AS isInternal, approved_at AS approvedAt,
         created_at AS createdAt, updated_at AS updatedAt";

switch ($method) {

    case 'GET': {
        $type = $session['type'] ?? 'staff';

        if ($id) {
            $p = db_one("SELECT $cols FROM projects WHERE id = ?", [$id]);
            if (!$p) json_err(404, 'Projekt nicht gefunden.');
            if (!can_read_project($p, $session)) json_err(403, 'Keine Berechtigung.');
            $p['files'] = list_files($id);
            json_ok($p);
        }

        if ($type === 'customer') {
            $rows = db_all("SELECT $cols FROM projects WHERE customer_id = ? ORDER BY COALESCE(deadline, posting_date, shoot_date, created_at) DESC",
                           [$session['cid']]);
            json_ok($rows);
        }

        // staff
        if (has_role('admin', 'manager')) {
            $rows = db_all("SELECT $cols FROM projects ORDER BY COALESCE(deadline, posting_date, shoot_date, created_at) DESC");
        } else {
            // Videograf/Cutter: nur eigene
            $uid = $session['uid'];
            $rows = db_all(
                "SELECT $cols FROM projects
                  WHERE videograf_id = ? OR cutter_id = ?
                  ORDER BY COALESCE(deadline, posting_date, shoot_date, created_at) DESC",
                [$uid, $uid]
            );
        }
        json_ok($rows);
    }

    case 'POST': {
        require_role('admin', 'manager');
        $b = input_json();
        $title = s($b['title'] ?? null, 190);
        if (!$title) json_err(400, 'title ist Pflicht.');
        $newId = $b['id'] ?? uid('p');

        $params = build_project_params($b, true);
        $params['id']    = $newId;
        $params['title'] = $title;

        $colNames = array_keys($params);
        $sql = "INSERT INTO projects (" . implode(',', array_map(fn($c) => "`$c`", $colNames)) . ")
                VALUES (" . implode(',', array_fill(0, count($colNames), '?')) . ")";

        try {
            db_exec($sql, array_values($params));
        } catch (Throwable $e) {
            log_err('project create', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte Projekt nicht anlegen.');
        }
        log_activity('project', $newId, 'created');
        $row = db_one("SELECT $cols FROM projects WHERE id = ?", [$newId]);
        $row['files'] = list_files($newId);
        json_ok($row, 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        $cur = db_one("SELECT id, videograf_id, cutter_id, customer_id, status FROM projects WHERE id = ?", [$id]);
        if (!$cur) json_err(404, 'Projekt nicht gefunden.');

        $b = input_json();
        $isPriv = has_role('admin', 'manager');
        $isVid  = $session['uid'] === $cur['videograf_id'];
        $isCut  = $session['uid'] === $cur['cutter_id'];

        if (!$isPriv && !$isVid && !$isCut) {
            json_err(403, 'Keine Berechtigung.');
        }

        // Videograf/Cutter dürfen nur status (+ approved_at via Status 'freigegeben') ändern
        if (!$isPriv) {
            $b = array_intersect_key($b, ['status' => 1]);
        }

        $params = build_project_params($b, false);
        if (!$params) json_ok(['id' => $id]);

        $set = [];
        $vals = [];
        foreach ($params as $c => $v) {
            $set[]  = "`$c` = ?";
            $vals[] = $v;
        }
        $vals[] = $id;

        try {
            db_exec("UPDATE projects SET " . implode(', ', $set) . " WHERE id = ?", $vals);
        } catch (Throwable $e) {
            log_err('project update', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte Projekt nicht speichern.');
        }

        log_activity('project', $id, 'edited', ['fields' => array_keys($params)]);
        $row = db_one("SELECT $cols FROM projects WHERE id = ?", [$id]);
        $row['files'] = list_files($id);
        json_ok($row);
    }

    case 'DELETE': {
        require_role('admin', 'manager');
        if (!$id) json_err(400, 'id fehlt.');
        db_exec("DELETE FROM projects WHERE id = ?", [$id]);
        log_activity('project', $id, 'deleted');
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}

// ---------------------------------------------------------------------------

function can_read_project(array $p, array $session): bool
{
    if (($session['type'] ?? '') === 'customer') {
        return $p['customerId'] === $session['cid'];
    }
    if (has_role('admin', 'manager')) return true;
    return $session['uid'] === $p['videografId'] || $session['uid'] === $p['cutterId'];
}

function build_project_params(array $b, bool $forInsert): array
{
    $valid = ['skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','archiviert'];
    $out = [];

    if (array_key_exists('title', $b))         $out['title']        = s($b['title'], 190);
    if (array_key_exists('customerId', $b))    $out['customer_id']  = $b['customerId']  ?: null;
    if (array_key_exists('videografId', $b))   $out['videograf_id'] = $b['videografId'] ?: null;
    if (array_key_exists('cutterId', $b))      $out['cutter_id']    = $b['cutterId']    ?: null;
    if (array_key_exists('shootDate', $b))     $out['shoot_date']   = as_date((string)$b['shootDate']);
    if (array_key_exists('shootDayId', $b))    $out['shoot_day_id'] = $b['shootDayId']  ?: null;
    if (array_key_exists('deadline', $b))      $out['deadline']     = as_date((string)$b['deadline']);
    if (array_key_exists('postingDate', $b))   $out['posting_date'] = as_date((string)$b['postingDate']);
    if (array_key_exists('script', $b))        $out['script']       = $b['script'] !== null ? (string)$b['script'] : null;
    if (array_key_exists('isInternal', $b))    $out['is_internal']  = as_bool($b['isInternal']);
    if (array_key_exists('status', $b)) {
        $st = $b['status'];
        if (!in_array($st, $valid, true)) json_err(400, 'Ungültiger Status.');
        $out['status'] = $st;
        if ($st === 'freigegeben') {
            $out['approved_at'] = date('Y-m-d H:i:s');
        }
    }

    if ($forInsert && !isset($out['status'])) {
        $out['status'] = 'skript';
    }

    return $out;
}

function list_files(string $projectId): array
{
    return db_all(
        "SELECT id, kind, filename, mime, size, path, uploaded_by AS uploadedBy, uploaded_at AS uploadedAt
           FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC",
        [$projectId]
    );
}
