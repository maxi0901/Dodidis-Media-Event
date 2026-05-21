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
    'uploads_path'     => __DIR__ . '/uploads',
    'uploads_url'      => '/agenturtool/uploads',
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
