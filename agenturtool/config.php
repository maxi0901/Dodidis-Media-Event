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
    'session_name'    => 'AGENTUR_SID',
    'session_max_age' => 14 * 24 * 60 * 60, // 14 Tage
    'cookie_secure'   => true,              // bei HTTPS-Betrieb true lassen
    'cookie_path'     => '/agenturtool/',

    // --- Uploads ---
    // Avatare + öffentliche Bilder (innerhalb Webroot, per URL erreichbar)
    'uploads_path'     => __DIR__ . '/uploads',
    'uploads_url'      => '/agenturtool/uploads',
    // Vertrauliche Dokumente (Verträge, Projektdateien) – AUSSERHALB Webroot
    // dirname(__DIR__, 2) geht 2 Ebenen über agenturtool/ hinaus:
    //   /var/www/vhosts/domain.de/httpdocs/agenturtool → /var/www/vhosts/domain.de/
    // Auf Plesk/cPanel ist das außerhalb des öffentlichen Webroots.
    'private_path'     => dirname(__DIR__, 2) . '/private_uploads',
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
    ],
    'max_upload_bytes' => 50 * 1024 * 1024, // 50 MB

    // --- Migration ---
    // Wenn nicht leer, kann migrate_legacy.php via ?token=… aus dem Browser gestartet werden
    'migration_token' => '',
];
