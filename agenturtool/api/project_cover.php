<?php
declare(strict_types=1);

/**
 * Projekt-Vorschaubild (Cover). Ein Bild pro Projekt — im neuen Modal-Vorschau-
 * bereich setzbar und beim Instagram-Reel-Posting als cover_url übergeben.
 *
 *   GET  ?project_id=<id>                      → Cover-Bild inline (Anzeige)
 *   POST ?project_id=<id>&filename=<name>      → Cover setzen (Body = Bilddaten)
 *   DELETE ?project_id=<id>                    → Cover entfernen
 *
 * Cover wird als Asset (kind='cover') auf dem NAS gespeichert, damit
 * media_public.php es signiert an Metas Fetcher ausliefern kann.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/NasWebDAV.php';
require_once __DIR__ . '/nas_provision.php';

$session = require_login();
if (($session['type'] ?? '') !== 'staff') json_err(403, 'Nur Mitarbeiter.');

$method    = $_SERVER['REQUEST_METHOD'];
$projectId = trim((string)($_GET['project_id'] ?? ''));
if ($projectId === '') json_err(400, 'project_id fehlt.');

// ── GET — Cover-Bild inline anzeigen ─────────────────────────────────────────
if ($method === 'GET') {
    requireProjectAccess($projectId, $session);
    $p   = db_one("SELECT cover_asset_id FROM projects WHERE id = ?", [$projectId]);
    $cid = (string)($p['cover_asset_id'] ?? '');
    if ($cid === '') { http_response_code(404); exit; }
    $a = db_one("SELECT nas_key, filename FROM assets WHERE id = ? AND status = 'stored'", [$cid]);
    if (!$a) { http_response_code(404); exit; }

    @set_time_limit(0);
    while (ob_get_level() > 0) ob_end_clean();
    try {
        (new NasWebDAV())->passthru((string)$a['nas_key'], (string)$a['filename'], 'inline');
    } catch (\Throwable $e) {
        http_response_code(502);
        exit('Cover derzeit nicht verfügbar');
    }
    exit;
}

// ── POST — Cover setzen ──────────────────────────────────────────────────────
if ($method === 'POST') {
    require_csrf();
    if (!has_role('admin', 'manager')) json_err(403, 'Nur Admin/Manager dürfen das Cover setzen.');
    requireProjectAccess($projectId, $session);

    $ctype = (string)($_SERVER['CONTENT_TYPE'] ?? 'application/octet-stream');
    if (stripos($ctype, 'image/') !== 0) json_err(400, 'Nur Bilddateien sind als Cover erlaubt.');

    $p = db_one("SELECT nas_folder, customer_id, cover_asset_id FROM projects WHERE id = ?", [$projectId]);
    if (!$p) json_err(404, 'Projekt nicht gefunden.');
    if (empty($p['nas_folder'])) {
        try { $p['nas_folder'] = nas_provision_project($projectId); }
        catch (\Throwable $e) { json_err(502, 'NAS-Ordner konnten nicht angelegt werden: ' . $e->getMessage()); }
    }

    $filename = trim((string)($_GET['filename'] ?? 'cover.jpg'));
    $safe = ltrim(substr(preg_replace('/[\/\\\\]/', '_', $filename), 0, 200), '.') ?: 'cover.jpg';
    $assetId = uid('na');
    $nasKey  = $p['nas_folder'] . '/_cover/' . $assetId . '_' . $safe;
    $len     = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

    try {
        (new NasWebDAV())->putStream($nasKey, $len, $ctype);
    } catch (\Throwable $e) {
        json_err(502, 'Cover-Upload fehlgeschlagen: ' . $e->getMessage());
    }

    db_exec(
        "INSERT INTO assets
           (id, project_id, customer_id, kind, nas_key, filename, content_type, size_bytes, status, uploaded_by, confirmed_at)
         VALUES (?, ?, ?, 'cover', ?, ?, ?, ?, 'stored', ?, NOW())",
        [$assetId, $projectId, $p['customer_id'] ?? null, $nasKey, $safe, $ctype, $len ?: null, $session['uid']]
    );

    $old = (string)($p['cover_asset_id'] ?? '');
    db_exec("UPDATE projects SET cover_asset_id = ? WHERE id = ?", [$assetId, $projectId]);

    // Altes Cover aufräumen (NAS-Datei + Asset) — best effort
    if ($old !== '' && $old !== $assetId) {
        try {
            $oa = db_one("SELECT nas_key FROM assets WHERE id = ?", [$old]);
            if ($oa) { try { (new NasWebDAV())->delete((string)$oa['nas_key']); } catch (\Throwable $_) {} }
            db_exec("DELETE FROM assets WHERE id = ?", [$old]);
        } catch (\Throwable $_) {}
    }

    log_activity('project', $projectId, 'cover_set', ['asset' => $assetId]);
    json_ok(['coverAssetId' => $assetId]);
}

// ── DELETE — Cover entfernen ─────────────────────────────────────────────────
if ($method === 'DELETE') {
    require_csrf();
    if (!has_role('admin', 'manager')) json_err(403, 'Nur Admin/Manager.');
    requireProjectAccess($projectId, $session);
    $p   = db_one("SELECT cover_asset_id FROM projects WHERE id = ?", [$projectId]);
    $cid = (string)($p['cover_asset_id'] ?? '');
    db_exec("UPDATE projects SET cover_asset_id = NULL WHERE id = ?", [$projectId]);
    if ($cid !== '') {
        try {
            $oa = db_one("SELECT nas_key FROM assets WHERE id = ?", [$cid]);
            if ($oa) { try { (new NasWebDAV())->delete((string)$oa['nas_key']); } catch (\Throwable $_) {} }
            db_exec("DELETE FROM assets WHERE id = ?", [$cid]);
        } catch (\Throwable $_) {}
    }
    log_activity('project', $projectId, 'cover_removed');
    json_ok(['removed' => true]);
}

json_err(405, 'Methode nicht erlaubt.');
