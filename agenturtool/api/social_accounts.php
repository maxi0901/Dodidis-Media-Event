<?php
declare(strict_types=1);

/**
 * Verwaltung der verbundenen Social-Konten pro Kunde (Admin/Manager).
 * Access-Tokens werden NIE ausgeliefert — nur Status/Metadaten.
 *
 *   GET    ?customer_id=   — verbundene Konten eines Kunden
 *   GET    (ohne Param)    — Status aller Kunden (verbunden ja/nein)
 *   DELETE ?id=            — Konto trennen (Token wird gelöscht)
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/MetaClient.php';

$session = require_login();
$method  = $_SERVER['REQUEST_METHOD'];
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) require_csrf();
if (($session['type'] ?? '') === 'customer' || !has_role('admin', 'manager')) {
    json_err(403, 'Nur Manager oder Admins.');
}

// Ob die Meta-App überhaupt konfiguriert ist (für Hinweise in der UI)
$metaConfigured = false;
try { $metaConfigured = (new MetaClient('x'))->isConfigured(); } catch (\Throwable $e) {}

$cols = "id, customer_id AS customerId, platform, account_label AS accountLabel,
         external_id AS externalId, page_id AS pageId, status,
         token_expires_at AS tokenExpiresAt, created_at AS createdAt";

switch ($method) {
    case 'GET': {
        $customerId = trim((string)($_GET['customer_id'] ?? ''));
        if ($customerId !== '') {
            $rows = db_all("SELECT $cols FROM social_accounts WHERE customer_id = ? ORDER BY platform", [$customerId]);
            json_ok(['metaConfigured' => $metaConfigured, 'accounts' => $rows]);
        }
        // Übersicht: welche Kunden haben mindestens ein verbundenes Konto
        $rows = db_all(
            "SELECT c.id AS customerId, c.name AS customerName,
                    COUNT(sa.id) AS connectedCount
               FROM customers c
          LEFT JOIN social_accounts sa ON sa.customer_id = c.id AND sa.status = 'connected'
           GROUP BY c.id, c.name
           ORDER BY c.name"
        );
        foreach ($rows as &$r) { $r['connectedCount'] = (int)$r['connectedCount']; }
        json_ok(['metaConfigured' => $metaConfigured, 'customers' => $rows]);
    }

    case 'DELETE': {
        $id = trim((string)($_GET['id'] ?? ''));
        if ($id === '') json_err(400, 'id fehlt.');
        $a = db_one("SELECT id, customer_id FROM social_accounts WHERE id = ?", [$id]);
        if (!$a) json_err(404, 'Konto nicht gefunden.');
        db_exec("DELETE FROM social_accounts WHERE id = ?", [$id]);
        log_activity('social_account', (string)$a['customer_id'], 'disconnected', ['id' => $id]);
        json_ok(['id' => $id]);
    }

    default:
        json_err(405, 'Methode nicht erlaubt.');
}
