<?php
declare(strict_types=1);

/**
 * Gemeinsame Helfer für manuell auf dem NAS abgelegte Dateien (ohne DB-Eintrag).
 * Wird von nas_assets.php (Download) und nas_media_view.php (Inline-Ansicht)
 * genutzt. Setzt voraus, dass response.php, db.php und access.php bereits
 * eingebunden sind (require_login/requireProjectAccess/json_err verfügbar).
 */

/**
 * Löst eine virtuelle „nas:<kind>:<filename>"-ID sicher zu einem NAS-Key auf.
 * Prüft Projektzugriff und entfernt jegliche Pfadanteile (kein Traversal).
 *
 * @return array{0:string,1:string,2:string} [nas_key, filename, content_type]
 */
function nas_resolve_manual(string $vid, string $projectId, array $session): array
{
    requireProjectAccess($projectId, $session);

    // Format: nas:<kind>:<filename> — der Dateiname darf Doppelpunkte enthalten
    $parts = explode(':', $vid, 3);
    if (count($parts) !== 3 || $parts[0] !== 'nas') json_err(400, 'Ungültige Datei-Kennung.');
    $kind = $parts[1];
    $name = basename($parts[2]); // Pfadanteile hart entfernen

    if (!in_array($kind, ['raw', 'final'], true)) json_err(400, 'Ungültiger Ordner.');
    if ($name === '' || $name[0] === '.') json_err(400, 'Ungültiger Dateiname.');

    $p = db_one("SELECT nas_folder FROM projects WHERE id = ?", [$projectId]);
    if (!$p || empty($p['nas_folder'])) json_err(404, 'Projekt-Ordner nicht gefunden.');

    $key = $p['nas_folder'] . '/' . $kind . '/' . $name;
    return [$key, $name, nas_guess_ctype($name)];
}

function nas_guess_ctype(string $filename): string
{
    static $map = [
        'mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'webm' => 'video/webm',
        'avi' => 'video/x-msvideo', 'mkv' => 'video/x-matroska', 'm4v' => 'video/mp4',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif', 'webp' => 'image/webp', 'heic' => 'image/heic',
        'pdf' => 'application/pdf', 'zip' => 'application/zip',
    ];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return $map[$ext] ?? 'application/octet-stream';
}
