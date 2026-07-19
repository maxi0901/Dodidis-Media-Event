<?php
declare(strict_types=1);

/**
 * Resumable-Upload-Ticket. Prüft Projektzugriff + Rolle und liefert:
 *   - endpoint: die tus-Upload-Adresse (Caddy /files/ → tusd auf dem vServer)
 *   - token:    signierter Zielpfad (HMAC). Der vServer-Hook lässt ihn nach dem
 *               Upload von tus_verify.php prüfen und legt die Datei dann per
 *               WebDAV im NAS-Projektordner ab (raw/final).
 *
 * Ablauf: Browser lädt (resumable) zum vServer → tusd puffert → Post-Finish-Hook
 * schiebt die fertige Datei auf den NAS und löscht den Puffer. Der PHP-Shared-
 * Host ist damit NICHT im Datenpfad.
 *
 *   GET ?project_id=<id>&kind=raw|final&filename=<name>
 *
 * Nur Mitarbeiter.
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/nas_env.php';
require_once __DIR__ . '/nas_provision.php';

$session = require_login();
if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen hochladen.');
}

$projectId  = trim((string)($_GET['project_id'] ?? ''));
$shootDayId = trim((string)($_GET['shoot_day_id'] ?? ''));
$customerId = trim((string)($_GET['customer_id'] ?? ''));
$kind       = trim((string)($_GET['kind'] ?? 'raw'));
$filename   = trim((string)($_GET['filename'] ?? ''));

if ($filename === '') json_err(400, 'filename ist Pflicht.');
$safe = ltrim(substr(preg_replace('/[\/\\\\]/', '_', $filename), 0, 200), '.');
if ($safe === '') json_err(400, 'Ungültiger Dateiname.');

if ($shootDayId !== '') {
    // ── Drehtag-Rohmaterial (kein Projekt) → {kunde}/{Kürzel} - Dreh {Datum}/ ──
    if (!has_role('admin', 'manager', 'videograf')) {
        json_err(403, 'Rohmaterial dürfen nur Videografen, Manager oder Admins hochladen.');
    }
    $sd = db_one("SELECT id, videograf_id, nas_folder FROM shoot_days WHERE id = ?", [$shootDayId]);
    if (!$sd) json_err(404, 'Drehtag nicht gefunden.');
    if (!has_role('admin', 'manager') && (string)($sd['videograf_id'] ?? '') !== (string)$session['uid']) {
        json_err(403, 'Nur eigene Drehtage.');
    }
    if (empty($sd['nas_folder'])) {
        try { $sd['nas_folder'] = nas_provision_shootday($shootDayId); }
        catch (\Throwable $e) { json_err(502, 'Drehtag-Ordner konnte nicht angelegt werden: ' . $e->getMessage()); }
    }
    $target = $sd['nas_folder'] . '/' . $safe;
} elseif ($customerId !== '') {
    // ── Kundenmaterial / B-Roll → {kunde}/{Kürzel} - Material/ ────────────────
    if (!has_role('admin', 'manager', 'videograf')) {
        json_err(403, 'Kundenmaterial dürfen nur Videografen, Manager oder Admins hochladen.');
    }
    $c = db_one("SELECT id, manager_id, material_folder FROM customers WHERE id = ?", [$customerId]);
    if (!$c) json_err(404, 'Kunde nicht gefunden.');
    if (!has_role('admin') && has_role('manager') && (string)($c['manager_id'] ?? '') !== (string)$session['uid']) {
        json_err(403, 'Nur eigene Kunden.');
    }
    if (empty($c['material_folder'])) {
        try { $c['material_folder'] = nas_provision_customer_material($customerId); }
        catch (\Throwable $e) { json_err(502, 'Kundenmaterial-Ordner konnte nicht angelegt werden: ' . $e->getMessage()); }
    }
    $target = $c['material_folder'] . '/' . $safe;
} else {
    // ── Projekt-Upload (raw/final) ───────────────────────────────────────────
    if ($projectId === '') json_err(400, 'project_id oder shoot_day_id ist Pflicht.');
    if (!in_array($kind, ['raw', 'final'], true)) json_err(400, "kind muss 'raw' oder 'final' sein.");

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

    // Projektordner sicherstellen und Zielpfad bauen.
    $p = db_one(
        "SELECT p.nas_folder, p.title, c.name AS customerName
           FROM projects p LEFT JOIN customers c ON c.id = p.customer_id
          WHERE p.id = ?",
        [$projectId]
    );
    if (!$p) json_err(404, 'Projekt nicht gefunden.');
    if (empty($p['nas_folder'])) {
        try { $p['nas_folder'] = nas_provision_project($projectId); }
        catch (\Throwable $e) { json_err(502, 'NAS-Ordner konnten nicht angelegt werden: ' . $e->getMessage()); }
    }

    // Finale Schnitte bekommen einen lesbaren Namen: „{Kürzel} - {Video} - Final".
    // Kollisionssicher: existiert die Datei schon (z. B. zweites Deliverable),
    // wird „ (2)", „ (3)" … angehängt — es wird NIE ein Final überschrieben.
    if ($kind === 'final') {
        $ext = (preg_match('/(\.[A-Za-z0-9]{1,8})$/', $safe, $mm)) ? $mm[1] : '';
        $kuerzel   = nas_kuerzel((string)($p['customerName'] ?? 'Intern'));
        $titleSafe = trim(preg_replace('/[\/\\\\]/', '_', (string)($p['title'] ?? 'Video')));
        $base      = "{$kuerzel} - {$titleSafe} - Final";
        try {
            $nas  = new NasWebDAV();
            $safe = $base . $ext;
            for ($i = 2; $i <= 50; $i++) {
                $h = $nas->head($p['nas_folder'] . '/final/' . $safe);
                if ((int)($h[1] ?? 0) === 0) break; // frei
                $safe = $base . ' (' . $i . ')' . $ext;
            }
        } catch (\Throwable $e) {
            // NAS gerade nicht erreichbar → eindeutigen Namen wählen (kein Überschreiben).
            $safe = $base . ' - ' . substr(bin2hex(random_bytes(3)), 0, 6) . $ext;
        }
    }
    $target = $p['nas_folder'] . '/' . $kind . '/' . $safe;
}

// tus-Endpoint = gleicher Host wie NAS_DAV_BASE + /files/ (Caddy → tusd).
$base = nas_credentials()['base'];
$u    = parse_url($base);
if (empty($u['host'])) json_err(500, 'NAS-Basis-URL nicht konfiguriert.');
$endpoint = ($u['scheme'] ?? 'https') . '://' . $u['host']
    . (isset($u['port']) ? ':' . $u['port'] : '') . '/files/';

// Signiertes Token: der vServer-Hook lässt es von tus_verify.php prüfen und
// legt die Datei dann unter $target ab. HMAC-Schlüssel = config.php api_key.
$cfg    = require __DIR__ . '/../config.php';
$apiKey = (string)($cfg['api_key'] ?? '');
if ($apiKey === '') json_err(500, 'api_key nicht konfiguriert.');

// Lebensdauer großzügig (30 Tage): ein langsamer/pausierter 100-GB-Upload kann
// Tage dauern und wird per Resume mit demselben Token abgeschlossen — der Hook
// prüft das Token erst beim (ggf. viel späteren) post-finish. Kürzer würde
// solche Uploads am vServer stranden lassen.
$b64url  = static fn(string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
$payload = $b64url(json_encode(['t' => $target, 'e' => time() + 30 * 24 * 3600], JSON_UNESCAPED_UNICODE));
$sig     = hash_hmac('sha256', $payload, $apiKey);
$token   = $payload . '.' . $sig;

json_ok([
    'endpoint' => $endpoint,
    'token'    => $token,
    'filename' => $safe,
]);
