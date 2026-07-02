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
$id      = $_GET['id'] ?? null;

if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    require_csrf();
}

if (($session['type'] ?? '') !== 'staff') {
    json_err(403, 'Nur Mitarbeiter dürfen auf NAS-Projekte zugreifen.');
}

switch ($method) {

    // ── GET ──────────────────────────────────────────────────────────────────
    case 'GET': {
        if ($id) {
            requireProjectAccess($id, $session);
            $p = db_one(
                "SELECT p.id, p.title, p.slug, p.nas_folder,
                        p.customer_id AS customerId, p.cutter_id AS cutterId,
                        p.videograf_id AS videografId, p.status,
                        p.shoot_date AS shootDate, p.created_at AS createdAt,
                        c.name AS customerName
                   FROM projects p
                   LEFT JOIN customers c ON c.id = p.customer_id
                  WHERE p.id = ?",
                [$id]
            );
            if (!$p) json_err(404, 'Projekt nicht gefunden.');
            $p['cutters'] = nas_project_cutters($id);
            $p['assets']  = nas_project_assets($id, $session);
            json_ok($p);
        }

        if (has_role('admin', 'manager')) {
            $rows = db_all(
                "SELECT p.id, p.title, p.slug, p.nas_folder,
                        p.customer_id AS customerId, p.status,
                        p.shoot_date AS shootDate, p.created_at AS createdAt,
                        c.name AS customerName
                   FROM projects p
                   LEFT JOIN customers c ON c.id = p.customer_id
                  WHERE p.nas_folder IS NOT NULL AND p.nas_folder <> ''
                  ORDER BY p.created_at DESC"
            );
        } else {
            $uid = $session['uid'];
            $rows = db_all(
                "SELECT p.id, p.title, p.slug, p.nas_folder,
                        p.customer_id AS customerId, p.status,
                        p.shoot_date AS shootDate, p.created_at AS createdAt,
                        c.name AS customerName
                   FROM projects p
                   LEFT JOIN customers c ON c.id = p.customer_id
                  WHERE p.nas_folder IS NOT NULL AND p.nas_folder <> ''
                    AND (
                        p.cutter_id    = ?
                     OR p.videograf_id = ?
                     OR EXISTS (
                            SELECT 1 FROM project_cutters pc
                             WHERE pc.project_id = p.id AND pc.user_id = ?
                        )
                    )
                  ORDER BY p.created_at DESC",
                [$uid, $uid, $uid]
            );
        }
        json_ok($rows);
    }

    // ── POST — provision NAS folders for an existing project ─────────────────
    case 'POST': {
        require_role('admin', 'manager');
        $b = input_json();

        $projectId = trim((string)($b['id'] ?? $b['projectId'] ?? ''));
        if ($projectId === '') json_err(400, 'id (bestehende Projekt-ID) ist Pflicht.');

        $p = db_one(
            "SELECT p.id, p.title, p.slug, p.nas_folder,
                    p.shoot_date AS shootDate,
                    p.customer_id AS customerId,
                    c.name AS customerName
               FROM projects p
               LEFT JOIN customers c ON c.id = p.customer_id
              WHERE p.id = ?",
            [$projectId]
        );
        if (!$p) json_err(404, 'Projekt nicht gefunden.');
        if (!empty($p['nas_folder'])) {
            json_err(409, 'NAS-Ordner existiert bereits: ' . $p['nas_folder']);
        }

        try {
            nas_provision_project($projectId);
        } catch (\Throwable $e) {
            json_err(502, 'NAS nicht erreichbar: ' . $e->getMessage());
        }

        // M:N cutter assignment
        $cutterIds = array_values(array_filter(
            (array)($b['cutterIds'] ?? []),
            fn($v) => is_string($v) && $v !== ''
        ));
        foreach ($cutterIds as $cid) {
            try {
                db_exec(
                    "INSERT IGNORE INTO project_cutters (project_id, user_id) VALUES (?, ?)",
                    [$projectId, $cid]
                );
            } catch (\Throwable $_) {}
        }
        // Keep legacy cutter_id in sync with primary cutter
        if (count($cutterIds) === 1) {
            db_exec("UPDATE projects SET cutter_id = ? WHERE id = ?", [reset($cutterIds), $projectId]);
        }

        $row = db_one(
            "SELECT p.id, p.title, p.slug, p.nas_folder,
                    p.customer_id AS customerId, p.status,
                    p.shoot_date AS shootDate
               FROM projects p WHERE p.id = ?",
            [$projectId]
        );
        $row['cutters'] = nas_project_cutters($projectId);
        json_ok($row, 201);
    }

    // ── PUT ?id= — update cutter assignments ─────────────────────────────────
    case 'PUT': {
        require_role('admin', 'manager');
        if (!$id) json_err(400, 'id fehlt.');
        $p = db_one("SELECT id FROM projects WHERE id = ?", [$id]);
        if (!$p) json_err(404, 'Projekt nicht gefunden.');

        $b = input_json();
        if (array_key_exists('cutterIds', $b)) {
            db_exec("DELETE FROM project_cutters WHERE project_id = ?", [$id]);
            $cutterIds = array_values(array_filter(
                (array)$b['cutterIds'],
                fn($v) => is_string($v) && $v !== ''
            ));
            foreach ($cutterIds as $cid) {
                try {
                    db_exec(
                        "INSERT IGNORE INTO project_cutters (project_id, user_id) VALUES (?, ?)",
                        [$id, $cid]
                    );
                } catch (\Throwable $_) {}
            }
        }

        log_activity('nas_project', $id, 'cutters_updated');
        $row = db_one(
            "SELECT id, title, slug, nas_folder FROM projects WHERE id = ?",
            [$id]
        );
        $row['cutters'] = nas_project_cutters($id);
        json_ok($row);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}

// ── helpers ───────────────────────────────────────────────────────────────────

function nas_project_cutters(string $projectId): array
{
    return db_all(
        "SELECT u.id, u.name
           FROM project_cutters pc
           JOIN users u ON u.id = pc.user_id
          WHERE pc.project_id = ?
          ORDER BY u.name",
        [$projectId]
    );
}

function nas_project_assets(string $projectId, array $session): array
{
    $rows = db_all(
        "SELECT id, kind, filename,
                content_type AS contentType,
                size_bytes AS sizeBytes,
                status, created_at AS createdAt
           FROM assets
          WHERE project_id = ?
          ORDER BY created_at DESC",
        [$projectId]
    );
    // Non-admin/manager only see confirmed assets
    if (!has_role('admin', 'manager')) {
        $rows = array_values(array_filter($rows, fn($r) => $r['status'] === 'stored'));
    }
    return $rows;
}
