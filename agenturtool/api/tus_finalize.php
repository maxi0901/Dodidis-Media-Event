<?php
declare(strict_types=1);

/**
 * Abschluss eines resumable Uploads. Nachdem tusd die (ggf. 100 GB große) Datei
 * vollständig im Staging-Ordner des NAS geschrieben hat, verschiebt dieser
 * Endpoint sie per WebDAV-MOVE in den Projektordner raw/ bzw. final/ — ein
 * sofortiger Rename auf dem NAS, kein erneuter Transfer.
 *
 * Danach erscheint die Datei automatisch über die bestehende PROPFIND-Erkennung
 * (manuell abgelegte NAS-Dateien) in der Medienliste — kein extra DB-Eintrag.
 *
 *   POST { upload_id, project_id, kind:raw|final, filename }
 *
 * Nur Mitarbeiter (gleiche Rollen-/Zugriffsregeln wie der klassische Upload).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/NasWebDAV.php';
require_once __DIR__ . '/nas_provision.php';

/** Staging-Ordner auf dem NAS (unter NAS_DAV_BASE) — muss == tusd -dir sein. */
const TUS_STAGING = '_tus_staging';

$session = require_login();
if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen hochladen.');
}
require_csrf();

$b         = input_json();
$uploadId  = trim((string)($b['upload_id'] ?? ''));
$projectId = trim((string)($b['project_id'] ?? ''));
$kind      = trim((string)($b['kind'] ?? 'raw'));
$filename  = trim((string)($b['filename'] ?? ''));

if ($uploadId === '')  json_err(400, 'upload_id fehlt.');
if ($projectId === '') json_err(400, 'project_id ist Pflicht.');
if ($filename === '')  json_err(400, 'filename ist Pflicht.');
if (!in_array($kind, ['raw', 'final'], true)) json_err(400, "kind muss 'raw' oder 'final' sein.");

// tus-ID ist eine zufällige ID ohne Pfadtrenner — hart validieren gegen
// Path-Traversal (sonst könnte man beliebige NAS-Dateien verschieben).
$uploadId = preg_replace('/\.info$/', '', $uploadId);
if (!preg_match('/^[A-Za-z0-9._-]{8,128}$/', $uploadId) || str_contains($uploadId, '..')) {
    json_err(400, 'Ungültige upload_id.');
}

requireProjectAccess($projectId, $session);

if ($kind === 'raw'   && !has_role('admin', 'manager', 'videograf')) {
    json_err(403, 'Rohmaterial dürfen nur Videografen, Manager oder Admins hochladen.');
}
if ($kind === 'final' && !has_role('admin', 'manager', 'cutter')) {
    json_err(403, 'Finale Schnitte dürfen nur Cutter, Manager oder Admins hochladen.');
}
if ($kind === 'final' && !has_role('admin', 'manager')) {
    $ps = db_one("SELECT status FROM projects WHERE id = ?", [$projectId]);
    if ($ps && in_array((string)($ps['status'] ?? ''), ['freigegeben', 'archiviert'], true)) {
        json_err(409, 'Projekt ist bereits abgenommen — finale Schnitte können nicht mehr hochgeladen werden.');
    }
}

$p = db_one("SELECT nas_folder FROM projects WHERE id = ?", [$projectId]);
if (!$p) json_err(404, 'Projekt nicht gefunden.');
if (empty($p['nas_folder'])) {
    try { $p['nas_folder'] = nas_provision_project($projectId); }
    catch (\Throwable $e) { json_err(502, 'NAS-Ordner konnten nicht angelegt werden: ' . $e->getMessage()); }
}

// Dateiname säubern (echter Name bleibt erhalten → saubere Anzeige), keine
// Pfadtrenner, keine versteckte Datei (PROPFIND blendet '.'-Dateien aus).
$safe = preg_replace('/[\/\\\\]/', '_', $filename);
$safe = ltrim(substr($safe, 0, 200), '.');
if ($safe === '') json_err(400, 'Ungültiger Dateiname.');

$targetDir = $p['nas_folder'] . '/' . $kind;
$fromKey   = TUS_STAGING . '/' . $uploadId;
$toKey     = $targetDir . '/' . $safe;

try {
    $nas = new NasWebDAV();
    $nas->ensureDir($targetDir);          // Zielordner sicherstellen (idempotent)
    $nas->move($fromKey, $toKey, true);   // sofortiger Rename, auch bei 100 GB
    // tus-Sidecar aufräumen (best effort)
    try { $nas->delete(TUS_STAGING . '/' . $uploadId . '.info'); } catch (\Throwable $_) {}
} catch (\Throwable $e) {
    json_err(502, 'Datei konnte nicht in den Projektordner verschoben werden: ' . $e->getMessage());
}

log_activity('asset', 'manual', 'uploaded',
    ['project' => $projectId, 'kind' => $kind, 'file' => $safe, 'via' => 'resumable']);

json_ok(['filename' => $safe, 'kind' => $kind]);
