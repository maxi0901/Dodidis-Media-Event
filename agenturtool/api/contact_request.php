<?php
declare(strict_types=1);

/**
 * Öffentliches Kontaktformular der Landingpage → speichert die Anfrage im CRM
 * (Tabelle crm_requests) statt sie in eine Mail zu packen.
 *
 * ÖFFENTLICH erreichbar (kein Login/CSRF). Nur POST. Sichtbar/verwaltet werden
 * die Anfragen ausschließlich von Admins im Tool (CRM › Anfragen).
 */

require_once __DIR__ . '/../includes/response.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_err(405, 'POST erwartet.');
}

// JSON-Body bevorzugt, klassische Formularfelder als Fallback.
$b = input_json();
if (!$b) { $b = $_POST; }

$name    = trim((string)($b['name']    ?? ''));
$email   = trim((string)($b['email']   ?? ''));
$company = trim((string)($b['company'] ?? ''));
$message = trim((string)($b['message'] ?? ''));
$consent = $b['consent'] ?? false;
$hp      = trim((string)($b['website'] ?? '')); // Honeypot (echte Nutzer lassen es leer)

// Honeypot: Bots füllen versteckte Felder → freundlich „ok", aber nichts speichern.
if ($hp !== '') { json_ok(['id' => null]); }

if ($name === '' || $email === '' || $message === '') {
    json_err(400, 'Bitte Name, E-Mail und Nachricht ausfüllen.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_err(400, 'Bitte eine gültige E-Mail-Adresse angeben.');
}
$consentOk = in_array($consent, [true, 1, '1', 'on', 'true', 'yes'], true);
if (!$consentOk) {
    json_err(400, 'Bitte der Datenschutzerklärung zustimmen.');
}

$name    = mb_substr($name, 0, 190);
$email   = mb_substr($email, 0, 190);
$company = $company !== '' ? mb_substr($company, 0, 190) : null;
$message = mb_substr($message, 0, 5000);

$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$ip = $ip !== '' ? mb_substr($ip, 0, 45) : null;

try {
    $id = uid('crm');
    db_exec(
        "INSERT INTO crm_requests (id, name, email, company, message, ip)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$id, $name, $email, $company, $message, $ip]
    );
    json_ok(['id' => $id]);
} catch (\Throwable $e) {
    error_log('[contact_request] insert failed: ' . $e->getMessage());
    json_err(500, 'Anfrage konnte nicht gespeichert werden.');
}
