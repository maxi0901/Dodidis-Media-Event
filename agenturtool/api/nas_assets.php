<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/NasWebDAV.php';
require_once __DIR__ . '/nas_provision.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
$id      = $_GET['id']         ?? null;
$action  = $_GET['action']     ?? null;
$projId  = $_GET['project_id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen auf Assets zugreifen.');
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
          WHERE project_id = ? AND status = 'stored'
          ORDER BY created_at DESC",
        [$projId]
    );
    json_ok($rows);
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

    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $contentType   = $_SERVER['CONTENT_TYPE'] ?? (string)$a['content_type'];

    try {
        $nas = new NasWebDAV();
        $nas->putStream((string)$a['nas_key'], $contentLength, $contentType);
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
    log_activity('asset', $id, 'uploaded', ['size' => $contentLength]);
    json_ok(asset_to_doc(db_one("SELECT * FROM assets WHERE id = ?", [$id])));
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

    db_exec("DELETE FROM assets WHERE id = ?", [$id]);
    log_activity('asset', $id, 'deleted');
    json_ok(['id' => $id]);
}

json_err(400, 'Ungültige Anfrage. Benötigt action=prepare|confirm, id=..., oder project_id=...');

// ── helper ────────────────────────────────────────────────────────────────────

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
