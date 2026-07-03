<?php
declare(strict_types=1);

/**
 * Review-Cache für finale Schnitte auf dem Netcup-Webspace.
 *
 * Finale Videos werden beim Upload zusätzlich lokal mitgeschrieben (Tee),
 * damit sie im Agenturtool sofort abspielbar/spulbar sind, ohne sie jedes
 * Mal über den Tunnel vom NAS zu laden.
 *
 * Lebenszyklus: entsteht beim Final-Upload · stirbt bei Asset-Löschung
 * oder wenn das Projekt archiviert wird. Rohmaterial wird NIE gecacht.
 *
 * Der Pfad wird aus der Asset-ID abgeleitet — keine DB-Spalte nötig,
 * file_exists() ist die einzige Wahrheit.
 */

const NAS_CACHE_DIR = __DIR__ . '/../media_cache';

function nas_cache_path(string $assetId): string
{
    // Asset-IDs kommen aus uid() — defensiv trotzdem auf sicheres Alphabet filtern
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $assetId);
    return NAS_CACHE_DIR . '/' . $safe;
}

/** Cache-Datei eines Assets löschen (idempotent). */
function nas_cache_delete(string $assetId): void
{
    $p = nas_cache_path($assetId);
    if (is_file($p)) @unlink($p);
}

/** Alle Cache-Dateien eines Projekts löschen (z. B. beim Archivieren). */
function nas_cache_purge_project(string $projectId): void
{
    try {
        $rows = db_all("SELECT id FROM assets WHERE project_id = ?", [$projectId]);
        foreach ($rows as $r) {
            nas_cache_delete((string)$r['id']);
        }
    } catch (\Throwable $e) {
        error_log('[nas_cache] purge für Projekt ' . $projectId . ' fehlgeschlagen: ' . $e->getMessage());
    }
}
