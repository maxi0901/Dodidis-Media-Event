<?php
declare(strict_types=1);

require_once __DIR__ . '/NasWebDAV.php';

/**
 * Legt die NAS-Ordnerstruktur für ein Projekt an (idempotent).
 *
 *   {kunde}/{datum}_{projekt}__{projektId}/raw
 *   {kunde}/{datum}_{projekt}__{projektId}/final
 *
 * Gibt den nas_folder zurück. Existiert er bereits, wird er unverändert
 * zurückgegeben. Wirft bei NAS-Fehler (DB wird dann zurückgerollt, damit
 * der Aufruf wiederholbar bleibt).
 */
function nas_provision_project(string $projectId): string
{
    $p = db_one(
        "SELECT p.id, p.title, p.nas_folder,
                p.shoot_date AS shootDate,
                c.name AS customerName
           FROM projects p
           LEFT JOIN customers c ON c.id = p.customer_id
          WHERE p.id = ?",
        [$projectId]
    );
    if (!$p) {
        throw new \RuntimeException('Projekt nicht gefunden: ' . $projectId);
    }
    if (!empty($p['nas_folder'])) {
        return (string)$p['nas_folder'];
    }

    $clientSlug = slugify($p['customerName'] ?? 'intern');
    $projSlug   = slugify($p['title']);
    $dateStr    = $p['shootDate'] ? substr((string)$p['shootDate'], 0, 10) : date('Y-m-d');
    $nasFolder  = "{$clientSlug}/{$dateStr}_{$projSlug}__{$p['id']}";

    db_exec(
        "UPDATE projects SET slug = ?, nas_folder = ? WHERE id = ?",
        [$projSlug, $nasFolder, $projectId]
    );

    try {
        $nas = new NasWebDAV();
        $nas->ensureDir($nasFolder . '/raw');
        $nas->ensureDir($nasFolder . '/final');
    } catch (\Throwable $e) {
        // Zurückrollen, damit der nächste Versuch sauber startet
        db_exec("UPDATE projects SET slug = NULL, nas_folder = NULL WHERE id = ?", [$projectId]);
        throw $e;
    }

    log_activity('nas_project', $projectId, 'nas_folder_created', ['folder' => $nasFolder]);
    return $nasFolder;
}

/**
 * Best-effort-Variante: Fehler werden nur geloggt, nie geworfen.
 * Für das Auto-Anlegen bei Projekt-Erstellung/-Umwandlung — ein nicht
 * erreichbares NAS darf das Speichern des Projekts nie blockieren.
 * (Nachgeholt wird beim ersten Upload über nas_assets.php.)
 */
function nas_provision_project_quietly(string $projectId): void
{
    try {
        nas_provision_project($projectId);
    } catch (\Throwable $e) {
        error_log('[nas_provision] Auto-Anlegen für ' . $projectId . ' fehlgeschlagen: ' . $e->getMessage());
    }
}
