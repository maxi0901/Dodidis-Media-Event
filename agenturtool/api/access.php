<?php
declare(strict_types=1);

/**
 * Shared access helpers for the NAS media layer.
 * Must be loaded AFTER auth-check.php (uses has_role, json_err).
 */

/**
 * Slugify a string for NAS folder names.
 * German umlauts: ä→ae, ö→oe, ü→ue, ß→ss (also uppercase variants).
 * Non-alphanumeric characters become hyphens; leading/trailing hyphens stripped.
 */
function slugify(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
    ]);
    $s = (string)preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/**
 * Primary role string for the session user (highest wins).
 */
function userRole(array $session): string
{
    if (($session['type'] ?? '') === 'customer') return 'customer';
    $roles = (array)($session['roles'] ?? []);
    foreach (['admin', 'manager', 'videograf', 'cutter', 'mitarbeiter', 'contract_uploader'] as $r) {
        if (in_array($r, $roles, true)) return $r;
    }
    return 'mitarbeiter';
}

/**
 * Returns true when the session user may access a project's NAS media.
 *
 * - Admin / Manager : always
 * - Videograf       : if videograf_id matches
 * - Cutter          : if cutter_id matches OR entry exists in project_cutters
 */
function userCanAccessProject(string $projectId, array $session): bool
{
    if (has_role('admin', 'manager')) return true;
    if (($session['type'] ?? '') !== 'staff') return false;

    $uid = $session['uid'] ?? '';
    if ($uid === '') return false;

    $p = db_one(
        "SELECT videograf_id, cutter_id FROM projects WHERE id = ?",
        [$projectId]
    );
    if (!$p) return false;

    if ($p['videograf_id'] === $uid || $p['cutter_id'] === $uid) return true;

    // M:N assignment
    $row = db_one(
        "SELECT 1 FROM project_cutters WHERE project_id = ? AND user_id = ? LIMIT 1",
        [$projectId, $uid]
    );
    return $row !== null;
}

/**
 * Abort with 403 if the current session user may not access this project.
 */
function requireProjectAccess(string $projectId, array $session): void
{
    if (!userCanAccessProject($projectId, $session)) {
        json_err(403, 'Kein Zugriff auf dieses Projekt.');
    }
}
