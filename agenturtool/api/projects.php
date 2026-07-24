<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/nas_provision.php';
require_once __DIR__ . '/nas_cache.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = $_GET['id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

// NAS-Freigabelinks sind staff-intern: niemals an Kunden ausliefern (sonst könnten
// Kunden die Roh-/Export-/Freigabe-Links über die rohe API auslesen).
$isStaff = (($session['type'] ?? 'staff') === 'staff');
$cols = "id, title, customer_id AS customerId, videograf_id AS videografId,
         cutter_id AS cutterId, shoot_date AS shootDate, shoot_day_id AS shootDayId,
         deadline, posting_date AS postingDate, script, status,
         is_internal AS isInternal, approved_at AS approvedAt,
         created_at AS createdAt, updated_at AS updatedAt";
if ($isStaff) {
    $cols .= ", nas_rohmaterial_url AS nasRohmaterialUrl, nas_export_url AS nasExportUrl,
               nas_freigabe_url AS nasFreigabeUrl";
}

switch ($method) {

    case 'GET': {
        $type = $session['type'] ?? 'staff';

        if ($id) {
            $p = db_one("SELECT $cols FROM projects WHERE id = ?", [$id]);
            if (!$p) json_err(404, 'Projekt nicht gefunden.');
            if (!can_read_project($p, $session)) json_err(403, 'Keine Berechtigung.');
            try { $p['files'] = list_files($id); } catch (\Throwable $e) { $p['files'] = []; }
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
        // Idee mit Kunde = Projekt → NAS-Ordner automatisch anlegen (best-effort)
        if (!empty($params['customer_id'])) {
            nas_provision_project_quietly($newId);
        }
        $row = db_one("SELECT $cols FROM projects WHERE id = ?", [$newId]);
        try { $row['files'] = list_files($newId); } catch (\Throwable $e) { $row['files'] = []; }
        json_ok($row, 201);
    }

    case 'PUT': {
        if (!$id) json_err(400, 'id fehlt.');
        try {
            $cur = db_one("SELECT id, title, videograf_id, cutter_id, customer_id, status, shoot_date FROM projects WHERE id = ?", [$id]);
        } catch (\Throwable $e) {
            // status-Spalte fehlt – nachrüsten und erneut versuchen
            try {
                db()->exec("ALTER TABLE projects ADD COLUMN status ENUM('idee','skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','gepostet','archiviert') NOT NULL DEFAULT 'skript'");
            } catch (\Throwable $_) {}
            try {
                $cur = db_one("SELECT id, title, videograf_id, cutter_id, customer_id, status, shoot_date FROM projects WHERE id = ?", [$id]);
            } catch (\Throwable $e2) {
                $cur = db_one("SELECT id, title, videograf_id, cutter_id, customer_id, shoot_date FROM projects WHERE id = ?", [$id]);
                if ($cur !== null) $cur['status'] = 'skript';
            }
        }
        if (!$cur) json_err(404, 'Projekt nicht gefunden.');

        $b = input_json();
        $isPriv = has_role('admin', 'manager');
        $isVid  = $session['uid'] === $cur['videograf_id'];
        $isCut  = $session['uid'] === $cur['cutter_id'];

        if (!$isPriv && !$isVid && !$isCut) {
            json_err(403, 'Keine Berechtigung.');
        }

        // Videograf/Cutter dürfen nur status (+ approved_at via Status 'freigegeben') ändern.
        // Sonderrecht: der zugewiesene Cutter darf zusätzlich seinen Export-Link (03_Exporte_Cutter)
        // setzen. Rohmaterial- und Freigabe-Link bleiben admin/manager-only.
        if (!$isPriv) {
            $allowed = ['status' => 1];
            if ($isCut) $allowed['nasExportUrl'] = 1;
            $b = array_intersect_key($b, $allowed);
        }

        $params = build_project_params($b, false);
        error_log('[projects PUT] id=' . $id . ' uid=' . ($session['uid'] ?? '?') . ' isPriv=' . ($isPriv?'1':'0') . ' isVid=' . ($isVid?'1':'0') . ' isCut=' . ($isCut?'1':'0') . ' body=' . json_encode($b) . ' params=' . json_encode($params));
        if (!$params) json_ok(['id' => $id]);

        // Abnahme (freigegeben) ist Admin/Manager vorbehalten. Es gibt BEWUSST
        // KEINE harte Sperre mehr bei offenen Review-Kommentaren: Freigeben ist
        // immer möglich, unabhängig davon, ob alle Korrekturpunkte abgehakt sind
        // (das Abhaken ist entkoppelt von der Freigabe).
        if (($params['status'] ?? '') === 'freigegeben' && !$isPriv) {
            json_err(403, 'Freigeben dürfen nur Manager oder Admins.');
        }

        $set = [];
        $vals = [];
        foreach ($params as $c => $v) {
            $set[]  = "`$c` = ?";
            $vals[] = $v;
        }
        $vals[] = $id;

        try {
            $pdo = db();
            $stmt = $pdo->prepare("UPDATE projects SET " . implode(', ', $set) . " WHERE id = ?");
            $stmt->execute($vals);
            $affected = $stmt->rowCount();
            error_log('[projects PUT] UPDATE affected=' . $affected . ' sql=UPDATE projects SET ' . implode(', ', $set) . ' WHERE id=' . $id);
        } catch (Throwable $e) {
            log_err('project update', ['msg' => $e->getMessage()]);
            json_err(500, 'Konnte Projekt nicht speichern.');
        }

        // Drehtag-Verschiebung protokollieren + Kunden benachrichtigen
        if ($isPriv && array_key_exists('shoot_date', $params)) {
            $oldShootDate = $cur['shoot_date'] ?? null;
            $newShootDate = $params['shoot_date'] ?? null;
            if ($oldShootDate && $newShootDate && $oldShootDate !== $newShootDate) {
                try {
                    db_exec(
                        "INSERT INTO project_shootdate_history (project_id, old_shoot_date, new_shoot_date, changed_by)
                         VALUES (?, ?, ?, ?)",
                        [$id, $oldShootDate, $newShootDate, $session['uid']]
                    );
                } catch (\Throwable $e) {
                    error_log('[projects] project_shootdate_history insert: ' . $e->getMessage());
                }
                try {
                    $customerManager = db_one("SELECT manager_id FROM customers WHERE id = ?", [$cur['customer_id']]);
                    if (!empty($customerManager['manager_id'])) {
                        db_exec(
                            "INSERT INTO notifications (user_id, type, title, body, ref_id, ref_type)
                             VALUES (?, 'shootdate_moved', 'Drehtag verschoben',
                                     CONCAT('Ihr Drehtag wurde verschoben: ', ?, ' → ', ?), ?, 'project')",
                            [$customerManager['manager_id'], $oldShootDate, $newShootDate, $id]
                        );
                    }
                } catch (\Throwable $e) {
                    error_log('[projects] shootdate notification insert: ' . $e->getMessage());
                }
            }
        }

        // Idee bekommt (erstmals) einen Kunden = Umwandlung zum Projekt
        // → NAS-Ordner automatisch anlegen (best-effort, blockiert nie)
        if (!empty($params['customer_id']) && empty($cur['customer_id'])) {
            nas_provision_project_quietly($id);
        }

        // Abnahme/Archiv → alte Final-Versionen aufräumen (neueste bleibt komplett)
        if (in_array(($params['status'] ?? ''), ['freigegeben', 'archiviert'], true)) {
            nas_purge_superseded_finals($id);
        }

        // Archiviert → Review-Kopien auf Netcup freigeben (NAS bleibt unberührt)
        if (($params['status'] ?? '') === 'archiviert') {
            nas_cache_purge_project($id);
        }

        log_activity('project', $id, 'edited', ['fields' => array_keys($params)]);
        try {
            $row = db_one("SELECT $cols FROM projects WHERE id = ?", [$id]);
        } catch (\Throwable $e) {
            error_log('[projects PUT] SELECT with status failed: ' . $e->getMessage());
            $colsMin = "id, title, customer_id AS customerId, videograf_id AS videografId,
                        cutter_id AS cutterId, shoot_date AS shootDate, shoot_day_id AS shootDayId,
                        deadline, posting_date AS postingDate, script, status,
                        is_internal AS isInternal, approved_at AS approvedAt,
                        created_at AS createdAt";
            try {
                $row = db_one("SELECT $colsMin FROM projects WHERE id = ?", [$id]);
            } catch (\Throwable $e2) {
                error_log('[projects PUT] SELECT fallback also failed: ' . $e2->getMessage());
                $row = null;
            }
        }
        // Debug: prepared vs. non-prepared SELECT um PDO-Protokoll-Bug vs. Trigger zu unterscheiden
        try {
            // prepared statement (binary protocol)
            $dbgStmt = db()->prepare("SELECT status, HEX(status) AS statusHex FROM projects WHERE id = ?");
            $dbgStmt->execute([$id]);
            $dbgRow = $dbgStmt->fetch();
            error_log('[projects PUT] direct-status(prepared): val=' . var_export($dbgRow['status'] ?? null, true)
                . ' hex=' . ($dbgRow['statusHex'] ?? '?') . ' id=' . $id);
            // non-prepared query (text protocol) – zum Vergleich
            $quotedId = db()->quote($id);
            $dbgRow2  = db()->query("SELECT status, HEX(status) AS statusHex FROM projects WHERE id = $quotedId")->fetch();
            error_log('[projects PUT] direct-status(query):    val=' . var_export($dbgRow2['status'] ?? null, true)
                . ' hex=' . ($dbgRow2['statusHex'] ?? '?') . ' id=' . $id);
        } catch (\Throwable $e) {
            error_log('[projects PUT] direct-status check failed: ' . $e->getMessage());
        }
        // Defensiv: leerer status im SELECT-Ergebnis → gespeicherten Wert aus params nehmen
        if (($row['status'] ?? '') === '' && isset($params['status'])) {
            $row['status'] = $params['status'];
        }
        error_log('[projects PUT] response status=' . ($row['status'] ?? 'MISSING') . ' id=' . $id);
        try { $row['files'] = list_files($id); } catch (\Throwable $e) { $row['files'] = []; }
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
    $valid = ['idee','skript','geplant','gedreht','schnitt','fertig','korrektur','freigegeben','gepostet','archiviert'];
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
    if (array_key_exists('nasRohmaterialUrl', $b)) $out['nas_rohmaterial_url'] = $b['nasRohmaterialUrl'] !== null ? (string)$b['nasRohmaterialUrl'] : null;
    if (array_key_exists('nasExportUrl', $b))      $out['nas_export_url']      = $b['nasExportUrl'] !== null ? (string)$b['nasExportUrl'] : null;
    if (array_key_exists('nasFreigabeUrl', $b))    $out['nas_freigabe_url']    = $b['nasFreigabeUrl'] !== null ? (string)$b['nasFreigabeUrl'] : null;
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
