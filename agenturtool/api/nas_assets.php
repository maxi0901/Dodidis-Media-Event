<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/NasWebDAV.php';
require_once __DIR__ . '/nas_provision.php';
require_once __DIR__ . '/nas_cache.php';
require_once __DIR__ . '/nas_files.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = $_GET['id']           ?? null;
$action  = $_GET['action']       ?? null;
$projId  = $_GET['project_id']   ?? null;
$shootId = $_GET['shoot_day_id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen auf Assets zugreifen.');
}

/** Zugriff auf einen Drehtag prüfen: Admin/Manager immer; Videograf nur eigene;
 *  Cutter, wenn ein ihm zugewiesenes Projekt an diesem Drehtag hängt. */
function nas_shootday_access(array $sd, array $session): bool
{
    $uid = (string)$session['uid'];
    if (has_role('admin', 'manager')) return true;
    if ((string)($sd['videograf_id'] ?? '') === $uid) return true;
    return (bool)db_one(
        "SELECT 1 FROM projects WHERE shoot_day_id = ? AND cutter_id = ? LIMIT 1",
        [(string)$sd['id'], $uid]
    );
}

// ── Route: GET ?shoot_day_id= — Rohmaterial eines Drehtags auflisten ──────────
if ($method === 'GET' && $shootId && !$id && $action !== 'download') {
    $sd = db_one("SELECT id, videograf_id, nas_folder FROM shoot_days WHERE id = ?", [$shootId]);
    if (!$sd) json_err(404, 'Drehtag nicht gefunden.');
    if (!nas_shootday_access($sd, $session)) json_err(403, 'Kein Zugriff auf diesen Drehtag.');

    $rows   = [];
    $folder = (string)($sd['nas_folder'] ?? '');
    if ($folder !== '') {
        try {
            foreach ((new NasWebDAV())->listDir($folder) as $f) {
                $rows[] = [
                    'id'          => 'nas:sd:' . $f['name'],
                    'shootDayId'  => $shootId,
                    'kind'        => 'raw',
                    'filename'    => $f['name'],
                    'contentType' => $f['ctype'] ?: nas_guess_ctype($f['name']),
                    'sizeBytes'   => $f['size'],
                    'status'      => 'stored',
                    'manual'      => true,
                    'pending'     => false,
                ];
            }
        } catch (\Throwable $e) {
            error_log('[nas_assets] Drehtag-Listing ' . $shootId . ': ' . $e->getMessage());
        }
    }
    // Am vServer gepufferte Uploads dieses Drehtags mit anzeigen (buffering).
    try {
        $pend = db_all("SELECT id, filename, size_bytes AS sizeBytes, created_at
                          FROM pending_uploads WHERE shoot_day_id = ?", [$shootId]);
        if ($pend) {
            $onNas = [];
            foreach ($rows as $r) $onNas[$r['filename']] = true;
            $creds = nas_credentials();
            $pu    = parse_url($creds['base']);
            $vhost = ($pu['scheme'] ?? 'https') . '://' . ($pu['host'] ?? '')
                   . (isset($pu['port']) ? ':' . $pu['port'] : '');
            foreach ($pend as $row) {
                if (!empty($onNas[$row['filename']]) || strtotime((string)$row['created_at']) < time() - 7 * 86400) {
                    try { db_exec("DELETE FROM pending_uploads WHERE id = ?", [$row['id']]); } catch (\Throwable $_) {}
                    continue;
                }
                $rows[] = [
                    'id'          => 'pending:' . $row['id'],
                    'shootDayId'  => $shootId,
                    'kind'        => 'raw',
                    'filename'    => $row['filename'],
                    'contentType' => nas_guess_ctype($row['filename']),
                    'sizeBytes'   => $row['sizeBytes'] !== null ? (int)$row['sizeBytes'] : null,
                    'status'      => 'buffering',
                    'manual'      => false,
                    'pending'     => true,
                    'vserverUrl'  => $vhost . '/files/' . rawurlencode($row['id']),
                ];
            }
        }
    } catch (\Throwable $e) { /* pending evtl. nicht migriert — ignorieren */ }

    json_ok($rows);
}

// ── Route: GET ?shoot_day_id=&action=download&file= — Drehtag-Datei laden ─────
if ($method === 'GET' && $shootId && $action === 'download') {
    $sd = db_one("SELECT id, videograf_id, nas_folder FROM shoot_days WHERE id = ?", [$shootId]);
    if (!$sd) json_err(404, 'Drehtag nicht gefunden.');
    if (!nas_shootday_access($sd, $session)) json_err(403, 'Kein Zugriff auf diesen Drehtag.');
    $folder = (string)($sd['nas_folder'] ?? '');
    $file   = ltrim(str_replace(['/', '\\'], '_', (string)($_GET['file'] ?? '')), '.');
    if ($folder === '' || $file === '') json_err(400, 'Datei fehlt.');
    (new NasWebDAV())->passthru($folder . '/' . $file, $file);
    exit;
}

// ── Route: GET ?project_id= — list assets for a project ──────────────────────
if ($method === 'GET' && $projId && !$id) {
    requireProjectAccess($projId, $session);
    $rows = db_all(
        "SELECT id, project_id AS projectId, kind, parent_id AS parentId,
                filename, content_type AS contentType,
                size_bytes AS sizeBytes, status,
                uploaded_by AS uploadedBy,
                created_at AS createdAt, confirmed_at AS confirmedAt
           FROM assets
          WHERE project_id = ? AND status = 'stored' AND kind <> 'cover'
          ORDER BY created_at DESC",
        [$projId]
    );

    // Offene Review-Kommentare pro Asset (separat, damit eine fehlende
    // asset_comments-Tabelle die Medienliste nicht bricht)
    $openByAsset = [];
    try {
        $counts = db_all(
            "SELECT asset_id, COUNT(*) AS n FROM asset_comments
              WHERE project_id = ? AND status = 'open' GROUP BY asset_id",
            [$projId]
        );
        foreach ($counts as $c) $openByAsset[$c['asset_id']] = (int)$c['n'];
    } catch (\Throwable $_) {}

    foreach ($rows as &$r) {
        $r['openComments'] = $openByAsset[$r['id']] ?? 0;
        $r['manual'] = false;
    }
    unset($r);

    // Manuell auf dem NAS abgelegte Dateien mit auflisten (ohne DB-Eintrag).
    // So tauchen Dateien auf, die jemand direkt in raw/ oder final/ kopiert hat
    // (z. B. großes Rohmaterial per SMB/lokal statt über den Upload-Durchlauf).
    $proj = db_one("SELECT nas_folder, customer_id FROM projects WHERE id = ?", [$projId]);
    if ($proj && !empty($proj['nas_folder'])) {
        $custId = $proj['customer_id'] ?? null;
        // Basenames aller bekannten DB-Assets (nas_key steht nicht im Listing-SELECT)
        $known = [];
        foreach (db_all("SELECT nas_key FROM assets WHERE project_id = ?", [$projId]) as $k) {
            $known[basename((string)$k['nas_key'])] = true;
        }

        try {
            $nas = new NasWebDAV();
            foreach (['raw', 'final'] as $kind) {
                foreach ($nas->listDir($proj['nas_folder'] . '/' . $kind) as $f) {
                    if (!empty($known[$f['name']])) continue; // schon als DB-Asset gelistet
                    $nasKey = $proj['nas_folder'] . '/' . $kind . '/' . $f['name'];
                    $ctype  = $f['ctype'] ?: nas_guess_ctype($f['name']);

                    // Finale Schnitte, die auf dem NAS liegen (per vServer-Upload ODER
                    // manuell abgelegt), als ECHTES Asset registrieren — damit sie im
                    // Posting-Planer, in der Freigabe und für Kommentare nutzbar sind,
                    // nicht nur virtuell. Idempotent (nas_key ist unique). Rohmaterial
                    // bleibt bewusst virtuell (braucht keinen DB-Eintrag).
                    if ($kind === 'final') {
                        $aid = uid('na');
                        try {
                            db_exec(
                                "INSERT INTO assets
                                   (id, project_id, customer_id, kind, parent_id, nas_key,
                                    filename, content_type, size_bytes, status, confirmed_at)
                                 VALUES (?, ?, ?, 'final', NULL, ?, ?, ?, ?, 'stored', NOW())",
                                [$aid, $projId, $custId, $nasKey, $f['name'], $ctype, $f['size']]
                            );
                            log_activity('asset', $aid, 'autoregistered', ['project' => $projId, 'nas_key' => $nasKey]);
                        } catch (\Throwable $e) {
                            // Duplikat/Rennen → vorhandenes Asset einlesen und dessen ID nutzen.
                            $ex  = db_one("SELECT id FROM assets WHERE nas_key = ?", [$nasKey]);
                            $aid = $ex['id'] ?? null;
                            if ($aid === null) continue; // konnte weder anlegen noch finden
                        }
                        $known[$f['name']] = true;
                        $rows[] = [
                            'id'          => $aid,
                            'projectId'   => $projId,
                            'kind'        => 'final',
                            'parentId'    => null,
                            'filename'    => $f['name'],
                            'contentType' => $ctype,
                            'sizeBytes'   => $f['size'],
                            'status'      => 'stored',
                            'uploadedBy'  => null,
                            'createdAt'   => null,
                            'confirmedAt' => null,
                            'openComments'=> 0,
                            'manual'      => false,
                        ];
                        continue;
                    }

                    $rows[] = [
                        'id'          => 'nas:' . $kind . ':' . $f['name'],
                        'projectId'   => $projId,
                        'kind'        => $kind,
                        'parentId'    => null,
                        'filename'    => $f['name'],
                        'contentType' => $ctype,
                        'sizeBytes'   => $f['size'],
                        'status'      => 'stored',
                        'uploadedBy'  => null,
                        'createdAt'   => null,
                        'confirmedAt' => null,
                        'openComments'=> 0,
                        'manual'      => true,
                    ];
                }
            }
        } catch (\Throwable $e) {
            error_log('[nas_assets] NAS-Listing für ' . $projId . ' fehlgeschlagen: ' . $e->getMessage());
        }
    }

    // Am vServer gepufferte, noch nicht auf dem NAS liegende Uploads mit anzeigen
    // (resumable Upload). Erscheinen als „pending", direkt vom vServer ladbar,
    // bis die Datei auf dem NAS liegt — dann Eintrag aufräumen.
    try {
        $pend = db_all(
            "SELECT id, kind, filename, size_bytes AS sizeBytes, uploaded_by AS uploadedBy, created_at
               FROM pending_uploads WHERE project_id = ?",
            [$projId]
        );
        if ($pend) {
            $onNas = [];
            foreach ($rows as $r) $onNas[$r['kind'] . '/' . $r['filename']] = true;

            $creds = nas_credentials();
            $pu    = parse_url($creds['base']);
            $vhost = ($pu['scheme'] ?? 'https') . '://' . ($pu['host'] ?? '')
                   . (isset($pu['port']) ? ':' . $pu['port'] : '');

            foreach ($pend as $row) {
                $key = $row['kind'] . '/' . $row['filename'];
                // schon auf dem NAS, oder verwaister Puffer (>7 Tage) → aufräumen.
                // 7 Tage (nicht 24h): ein am vServer gestrandeter Upload (NAS-Push
                // schlug transient fehl) bleibt sichtbar, bis der Retry-Timer ihn
                // gesichert hat — sonst verschwände er, obwohl noch ladbar.
                if (!empty($onNas[$key]) || strtotime((string)$row['created_at']) < time() - 7 * 86400) {
                    try { db_exec("DELETE FROM pending_uploads WHERE id = ?", [$row['id']]); } catch (\Throwable $_) {}
                    continue;
                }
                $rows[] = [
                    'id'          => 'pending:' . $row['id'],
                    'projectId'   => $projId,
                    'kind'        => $row['kind'],
                    'parentId'    => null,
                    'filename'    => $row['filename'],
                    'contentType' => nas_guess_ctype($row['filename']),
                    'sizeBytes'   => $row['sizeBytes'] !== null ? (int)$row['sizeBytes'] : null,
                    'status'      => 'buffering',
                    'uploadedBy'  => $row['uploadedBy'],
                    'createdAt'   => $row['created_at'],
                    'confirmedAt' => null,
                    'openComments'=> 0,
                    'manual'      => false,
                    'pending'     => true,
                    'vserverUrl'  => $vhost . '/files/' . rawurlencode($row['id']),
                ];
            }
        }
    } catch (\Throwable $e) {
        // pending_uploads evtl. noch nicht migriert — ignorieren
    }

    json_ok($rows);
}

// ── Route: POST ?action=pending — resumable Upload am vServer registrieren ────
if ($method === 'POST' && $action === 'pending') {
    $b          = input_json();
    $uploadId   = trim((string)($b['upload_id'] ?? ''));
    $projectId  = trim((string)($b['project_id'] ?? ''));
    $shootDayId = trim((string)($b['shoot_day_id'] ?? ''));
    $kind       = trim((string)($b['kind'] ?? 'raw'));
    $filename   = trim((string)($b['filename'] ?? ''));
    $size       = (int)($b['size'] ?? 0);

    if ($uploadId === '' || !preg_match('/^[A-Za-z0-9._-]{8,128}$/', $uploadId)) json_err(400, 'upload_id ungültig.');
    if ($filename === '') json_err(400, 'filename ist Pflicht.');
    $safe = ltrim(substr(preg_replace('/[\/\\\\]/', '_', $filename), 0, 200), '.');

    if ($shootDayId !== '') {
        // Drehtag-Rohmaterial (kein Projekt).
        if (!has_role('admin', 'manager', 'videograf')) json_err(403, 'Keine Berechtigung (Rohmaterial).');
        if (!db_one("SELECT id FROM shoot_days WHERE id = ?", [$shootDayId])) json_err(404, 'Drehtag nicht gefunden.');
        try {
            db_exec(
                "REPLACE INTO pending_uploads (id, project_id, shoot_day_id, kind, filename, size_bytes, uploaded_by)
                 VALUES (?, NULL, ?, 'raw', ?, ?, ?)",
                [$uploadId, $shootDayId, $safe, $size ?: null, $session['uid']]
            );
        } catch (\Throwable $e) { error_log('[nas_assets pending sd] ' . $e->getMessage()); }
        json_ok(['pending' => true]);
    }

    if ($projectId === '') json_err(400, 'project_id oder shoot_day_id ist Pflicht.');
    if (!in_array($kind, ['raw', 'final'], true)) json_err(400, "kind muss 'raw' oder 'final' sein.");

    requireProjectAccess($projectId, $session);
    if ($kind === 'raw'   && !has_role('admin', 'manager', 'videograf')) json_err(403, 'Keine Berechtigung (Rohmaterial).');
    if ($kind === 'final' && !has_role('admin', 'manager', 'cutter'))    json_err(403, 'Keine Berechtigung (finale Schnitte).');

    try {
        db_exec(
            "REPLACE INTO pending_uploads (id, project_id, kind, filename, size_bytes, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?)",
            [$uploadId, $projectId, $kind, $safe, $size ?: null, $session['uid']]
        );
    } catch (\Throwable $e) {
        error_log('[nas_assets pending] ' . $e->getMessage()); // nicht hart failen
    }
    json_ok(['pending' => true]);
}

// ── Route: POST ?action=prepare — create pending asset record ─────────────────
if ($method === 'POST' && $action === 'prepare') {
    $b          = input_json();
    $projectId  = trim((string)($b['project_id']   ?? ''));
    $kind       = trim((string)($b['kind']          ?? 'raw'));
    $filename   = trim((string)($b['filename']      ?? ''));
    $ctype      = trim((string)($b['content_type']  ?? 'application/octet-stream'));
    $parentId   = $b['parent_id'] ?? null;

    if ($projectId === '') json_err(400, 'project_id ist Pflicht.');
    if (!in_array($kind, ['raw', 'final'], true)) json_err(400, "kind muss 'raw' oder 'final' sein.");
    if ($filename === '') json_err(400, 'filename ist Pflicht.');

    requireProjectAccess($projectId, $session);

    // Rollen: Rohmaterial laden Videografen hoch, finale Schnitte Cutter.
    // Admin/Manager dürfen beides.
    if ($kind === 'raw' && !has_role('admin', 'manager', 'videograf')) {
        json_err(403, 'Rohmaterial dürfen nur Videografen, Manager oder Admins hochladen.');
    }
    if ($kind === 'final' && !has_role('admin', 'manager', 'cutter')) {
        json_err(403, 'Finale Schnitte dürfen nur Cutter, Manager oder Admins hochladen.');
    }

    $p = db_one("SELECT nas_folder FROM projects WHERE id = ?", [$projectId]);
    if (!$p) json_err(404, 'Projekt nicht gefunden.');

    // Nach der Abnahme sind finale Schnitte eingefroren — nur Admin/Manager
    // dürfen (z. B. für Nachlieferungen) noch hochladen.
    nas_assert_final_not_locked(['kind' => $kind, 'project_id' => $projectId], $session);
    if (empty($p['nas_folder'])) {
        // Ordner fehlen noch (z. B. NAS war beim Anlegen offline) → jetzt nachholen
        try {
            $p['nas_folder'] = nas_provision_project($projectId);
        } catch (\Throwable $e) {
            json_err(502, 'NAS-Ordner konnten nicht angelegt werden: ' . $e->getMessage());
        }
    }

    // Sanitize filename (keep extension, strip path separators)
    $safeFilename = preg_replace('/[\/\\\\]/', '_', $filename);
    $safeFilename = substr($safeFilename, 0, 200);

    $assetId = uid('na');
    $nasKey  = $p['nas_folder'] . '/' . $kind . '/' . $assetId . '_' . $safeFilename;

    $customerId = db_one("SELECT customer_id FROM projects WHERE id = ?", [$projectId]);

    db_exec(
        "INSERT INTO assets
           (id, project_id, customer_id, kind, parent_id, nas_key,
            filename, content_type, status, uploaded_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)",
        [
            $assetId,
            $projectId,
            $customerId['customer_id'] ?? null,
            $kind,
            $parentId ?: null,
            $nasKey,
            $safeFilename,
            $ctype,
            $session['uid'],
        ]
    );

    log_activity('asset', $assetId, 'prepared', ['project' => $projectId, 'kind' => $kind]);
    json_ok(asset_to_doc(db_one("SELECT * FROM assets WHERE id = ?", [$assetId])), 201);
}

// ── Route: POST ?id= &action=confirm — HEAD-verify on NAS ────────────────────
if ($method === 'POST' && $id && $action === 'confirm') {
    $a = db_one("SELECT * FROM assets WHERE id = ?", [$id]);
    if (!$a) json_err(404, 'Asset nicht gefunden.');
    requireProjectAccess((string)$a['project_id'], $session);
    if ($a['status'] !== 'stored') {
        nas_assert_final_not_locked($a, $session);
    }

    try {
        $nas = new NasWebDAV();
        [$ctype, $size] = $nas->head((string)$a['nas_key']);
    } catch (\Throwable $e) {
        json_err(502, 'NAS nicht erreichbar: ' . $e->getMessage());
    }

    if ($size === 0) {
        json_err(422, 'Datei wurde auf dem NAS nicht gefunden oder hat Größe 0.');
    }

    db_exec(
        "UPDATE assets
            SET status = 'stored', size_bytes = ?, confirmed_at = NOW()
          WHERE id = ?",
        [$size, $id]
    );
    log_activity('asset', $id, 'confirmed', ['size' => $size]);
    json_ok(asset_to_doc(db_one("SELECT * FROM assets WHERE id = ?", [$id])));
}

// ── Route: PUT ?id= — stream upload php://input → NAS ────────────────────────
if ($method === 'PUT' && $id) {
    $a = db_one("SELECT * FROM assets WHERE id = ?", [$id]);
    if (!$a) json_err(404, 'Asset nicht gefunden.');
    requireProjectAccess((string)$a['project_id'], $session);

    if ($a['status'] === 'stored') {
        json_err(409, 'Asset wurde bereits hochgeladen.');
    }
    if ($a['uploaded_by'] !== $session['uid'] && !has_role('admin', 'manager')) {
        json_err(403, 'Nur der ursprüngliche Uploader oder Admin/Manager darf dieses Asset ersetzen.');
    }

    // Abnahme-Sperre zum Zeitpunkt des tatsächlichen Uploads erneut prüfen —
    // ein vor der Freigabe vorbereitetes (pending) Asset darf danach nicht
    // mehr gespeichert werden.
    nas_assert_final_not_locked($a, $session);

    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $contentType   = $_SERVER['CONTENT_TYPE'] ?? (string)$a['content_type'];

    // Finale Schnitte: Review-Kopie auf Netcup beim Durchleiten mitschreiben,
    // damit der Player sie ohne Tunnel-Roundtrip abspielen kann. Raw bleibt
    // reines Streaming ohne Disk-Nutzung.
    $teePath = null;
    if ($a['kind'] === 'final') {
        @mkdir(NAS_CACHE_DIR, 0775, true);
        $teePath = nas_cache_path((string)$id);
    }

    try {
        $nas = new NasWebDAV();
        $nas->putStream((string)$a['nas_key'], $contentLength, $contentType, $teePath);
    } catch (\Throwable $e) {
        db_exec("UPDATE assets SET status = 'failed' WHERE id = ?", [$id]);
        json_err(502, 'NAS-Upload fehlgeschlagen: ' . $e->getMessage());
    }

    db_exec(
        "UPDATE assets
            SET status = 'stored',
                size_bytes   = ?,
                content_type = ?,
                confirmed_at = NOW()
          WHERE id = ?",
        [$contentLength ?: null, $contentType, $id]
    );
    // Auto-Status: neuer finaler Schnitt in aktiver Schnittphase → 'fertig'
    // (Review kann starten). Nur vorwärts, andere Stati bleiben unberührt.
    if ($a['kind'] === 'final') {
        try {
            db_exec(
                "UPDATE projects SET status = 'fertig' WHERE id = ? AND status IN ('schnitt', 'korrektur')",
                [$a['project_id']]
            );
        } catch (\Throwable $e) {
            error_log('[nas_assets] Auto-Status fertig: ' . $e->getMessage());
        }
    }

    log_activity('asset', $id, 'uploaded', ['size' => $contentLength]);
    json_ok(asset_to_doc(db_one("SELECT * FROM assets WHERE id = ?", [$id])));
}

// ── Route: GET ?id=nas:kind:name — manuelle NAS-Datei herunterladen ──────────
if ($method === 'GET' && $id && str_starts_with((string)$id, 'nas:')) {
    if (!$projId) json_err(400, 'project_id fehlt für manuelle NAS-Datei.');
    [$key, $fname, ] = nas_resolve_manual((string)$id, (string)$projId, $session);
    log_activity('asset', 'manual', 'downloaded', ['project' => $projId, 'file' => $fname]);
    @set_time_limit(0);          // große Downloads nicht nach max_execution_time killen
    ignore_user_abort(false);    // bei Client-Abbruch Skript beenden (kein Weiterladen)
    try {
        $nas = new NasWebDAV();
        while (ob_get_level() > 0) ob_end_clean();
        $nas->passthru($key, $fname);
    } catch (\Throwable $e) {
        if (!headers_sent()) {
            http_response_code(502);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'NAS nicht erreichbar: ' . $e->getMessage()]);
        }
    }
    exit;
}

// ── Route: GET ?id= — download (passthru from NAS) ───────────────────────────
if ($method === 'GET' && $id) {
    $a = db_one("SELECT * FROM assets WHERE id = ?", [$id]);
    if (!$a) json_err(404, 'Asset nicht gefunden.');
    requireProjectAccess((string)$a['project_id'], $session);

    if ($a['status'] !== 'stored') {
        json_err(409, 'Asset ist noch nicht vollständig gespeichert.');
    }

    log_activity('asset', $id, 'downloaded', ['by' => $session['uid']]);

    @set_time_limit(0);          // große Downloads nicht nach max_execution_time killen
    ignore_user_abort(false);    // bei Client-Abbruch Skript beenden
    try {
        $nas = new NasWebDAV();
        // Flush any output buffers so streaming works cleanly
        while (ob_get_level() > 0) ob_end_clean();
        $nas->passthru((string)$a['nas_key'], (string)$a['filename']);
    } catch (\Throwable $e) {
        // Headers may already be sent — just emit minimal error
        if (!headers_sent()) {
            http_response_code(502);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'NAS nicht erreichbar: ' . $e->getMessage()]);
        }
    }
    exit;
}

// ── Route: DELETE ?id= ────────────────────────────────────────────────────────
if ($method === 'DELETE' && $id) {
    require_role('admin', 'manager');
    $a = db_one("SELECT * FROM assets WHERE id = ?", [$id]);
    if (!$a) json_err(404, 'Asset nicht gefunden.');

    try {
        $nas = new NasWebDAV();
        $nas->delete((string)$a['nas_key']);
    } catch (\Throwable $e) {
        // Log but don't fail — NAS file may already be gone
        error_log('[nas_assets DELETE] NAS delete failed for ' . $a['nas_key'] . ': ' . $e->getMessage());
    }

    nas_cache_delete((string)$id);
    // Kommentare mitlöschen — sonst blockieren verwaiste offene Kommentare
    // die Projekt-Freigabe, ohne in der UI erreichbar zu sein
    try { db_exec("DELETE FROM asset_comments WHERE asset_id = ?", [$id]); } catch (\Throwable $_) {}
    db_exec("DELETE FROM assets WHERE id = ?", [$id]);
    log_activity('asset', $id, 'deleted');
    json_ok(['id' => $id]);
}

json_err(400, 'Ungültige Anfrage. Benötigt action=prepare|confirm, id=..., oder project_id=...');

// ── helper ────────────────────────────────────────────────────────────────────

/**
 * Abnahme-Sperre: Finale Schnitte sind nach Freigabe/Archivierung eingefroren.
 * Wird bei prepare UND beim tatsächlichen PUT/confirm geprüft, damit ein vor
 * der Freigabe vorbereitetes Asset danach nicht mehr gespeichert werden kann.
 * Admin/Manager dürfen weiterhin (Nachlieferungen).
 */
function nas_assert_final_not_locked(array $a, array $session): void
{
    if (($a['kind'] ?? '') !== 'final') return;
    if (has_role('admin', 'manager')) return;
    $p = db_one("SELECT status FROM projects WHERE id = ?", [$a['project_id']]);
    if ($p && in_array((string)($p['status'] ?? ''), ['freigegeben', 'archiviert'], true)) {
        json_err(409, 'Projekt ist bereits abgenommen — finale Schnitte können nicht mehr hochgeladen oder ersetzt werden.');
    }
}

function asset_to_doc(?array $a): array
{
    if (!$a) return [];
    return [
        'id'          => $a['id'],
        'projectId'   => $a['project_id'],
        'customerId'  => $a['customer_id'],
        'kind'        => $a['kind'],
        'parentId'    => $a['parent_id'],
        'nasKey'      => $a['nas_key'],
        'filename'    => $a['filename'],
        'contentType' => $a['content_type'],
        'sizeBytes'   => $a['size_bytes'] !== null ? (int)$a['size_bytes'] : null,
        'status'      => $a['status'],
        'uploadedBy'  => $a['uploaded_by'],
        'createdAt'   => $a['created_at'],
        'confirmedAt' => $a['confirmed_at'],
    ];
}
