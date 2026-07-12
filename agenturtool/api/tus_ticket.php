<?php
declare(strict_types=1);

/**
 * Resumable-Upload-Ticket. Prüft, ob der Nutzer für dieses Projekt + kind
 * (raw/final) hochladen darf, und liefert die tus-Upload-Adresse zurück.
 * Erst nach diesem OK startet das Frontend den eigentlichen (großen) Upload
 * direkt zum vServer/NAS — der PHP-Shared-Host ist damit NICHT im Datenpfad.
 *
 *   GET ?project_id=<id>&kind=raw|final
 *   → { endpoint, projectId, kind }
 *
 * Nur Mitarbeiter. Fertigstellung/Einsortieren macht danach tus_finalize.php.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/nas_env.php';

$session = require_login();
if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen hochladen.');
}

$projectId = trim((string)($_GET['project_id'] ?? ''));
$kind      = trim((string)($_GET['kind'] ?? 'raw'));

if ($projectId === '') json_err(400, 'project_id ist Pflicht.');
if (!in_array($kind, ['raw', 'final'], true)) json_err(400, "kind muss 'raw' oder 'final' sein.");

requireProjectAccess($projectId, $session);

// Gleiche Rollen-Regeln wie beim klassischen Upload (nas_assets.php prepare).
if ($kind === 'raw'   && !has_role('admin', 'manager', 'videograf')) {
    json_err(403, 'Rohmaterial dürfen nur Videografen, Manager oder Admins hochladen.');
}
if ($kind === 'final' && !has_role('admin', 'manager', 'cutter')) {
    json_err(403, 'Finale Schnitte dürfen nur Cutter, Manager oder Admins hochladen.');
}

// Abnahme-Sperre für finale Schnitte (nur Admin/Manager dürfen danach noch).
if ($kind === 'final' && !has_role('admin', 'manager')) {
    $p = db_one("SELECT status FROM projects WHERE id = ?", [$projectId]);
    if ($p && in_array((string)($p['status'] ?? ''), ['freigegeben', 'archiviert'], true)) {
        json_err(409, 'Projekt ist bereits abgenommen — finale Schnitte können nicht mehr hochgeladen werden.');
    }
}

// tus-Endpoint = gleicher Host wie der NAS-WebDAV (nas.dodidis-media.de), aber
// Pfad /files/ (Caddy leitet /files/* an den tusd-Container weiter). Folgt so
// automatisch einem Tunnel-Wechsel — nur NAS_DAV_BASE muss stimmen.
$base = nas_credentials()['base'];
$u    = parse_url($base);
if (empty($u['host'])) json_err(500, 'NAS-Basis-URL nicht konfiguriert.');
$endpoint = ($u['scheme'] ?? 'https') . '://' . $u['host']
    . (isset($u['port']) ? ':' . $u['port'] : '') . '/files/';

json_ok([
    'endpoint'  => $endpoint,
    'projectId' => $projectId,
    'kind'      => $kind,
]);
