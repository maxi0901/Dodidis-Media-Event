<?php
// Datenbank-Zugangsdaten + App-Konfiguration
// HINWEIS: Diese Datei darf nicht öffentlich auslieferbar sein
// (Apache/Nginx liefert .php nicht als Quelltext aus, solange PHP läuft).
return [
    // --- DB ---
    'host'     => '10.35.233.136',
    'port'     => 3306,
    'database' => 'k275333_dodidis-Media',
    'user'     => 'k275333_Dodidis-media',
    'password' => 'Tokarski12.',
    'charset'  => 'utf8mb4',

    // --- Sessions ---
    'session_name'         => 'AGENTUR_SID',
    'session_max_age'      =>  14 * 24 * 60 * 60, // 14 Tage (Standard)
    'session_remember_age' =>  90 * 24 * 60 * 60, // 90 Tage (Angemeldet bleiben)
    'cookie_secure'        => true,               // bei HTTPS-Betrieb true lassen
    'cookie_path'          => '/agenturtool/',

    // --- Uploads ---
    // Avatare + öffentliche Bilder (innerhalb Webroot, per URL erreichbar)
    'uploads_path'     => __DIR__ . '/uploads',
    'uploads_url'      => '/agenturtool/uploads',
    // Vertrauliche Dokumente (Verträge, Projektdateien)
    // Liegt innerhalb von agenturtool/ → PHP hat immer Schreibrechte.
    // Zugriff wird durch .htaccess + index.php blockiert (Apache + nginx).
    // Dateinamen enthalten einen zufälligen Prefix (uid), sind also nicht erratbar.
    'private_path'     => __DIR__ . '/private_uploads',
    'allowed_mimes'    => [
        'application/pdf',
        'application/msword',                                                                    // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',              // .docx
        'application/vnd.oasis.opendocument.text',                                              // .odt
        'application/vnd.ms-excel',                                                             // .xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',                   // .xlsx
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
        'text/plain',
        'video/mp4',
        'video/quicktime',
        'video/webm',
        'audio/webm',
        'audio/ogg',
        'audio/wav',
    ],
    'max_upload_bytes' => 2 * 1024 * 1024 * 1024, // 2 GB — Grenze NUR fuer Kundendateien/Vertraege (lokale Webhosting-Platte). Projekt-Medien laufen ueber den NAS-Stream (nas_assets.php) OHNE dieses Limit. Nicht unbegrenzt setzen, sonst kann die Webhosting-Platte volllaufen.

    // --- Migration ---
    // Wenn nicht leer, kann migrate_legacy.php via ?token=… aus dem Browser gestartet werden
    'migration_token' => '',

    // --- n8n / externe API (Maschine-zu-Maschine) ---
    // Gültiger Schlüssel für die content_*-Endpunkte (Header: X-API-KEY).
    // Leer = alle API-Key-Endpunkte antworten mit 401 (fail-closed).
    // Bei Bedarf rotieren: einfach neuen Wert eintragen.
    'api_key' => 'n8n_782ed8efb0d186e6140598deb85bec4986b80fcbbbdc8a59',
];
