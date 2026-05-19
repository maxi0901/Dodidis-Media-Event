<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/includes/helpers.php';
start_app_session();

// Nicht angemeldet → zum Login-Screen
if (empty($_SESSION['uid']) && empty($_SESSION['cid'])) {
    header('Location: login.php');
    exit;
}

// Session-Bundle fürs Frontend bauen
$session = [
    'type'            => $_SESSION['type'] ?? null,
    'uid'             => $_SESSION['uid']  ?? null,
    'cid'             => $_SESSION['cid']  ?? null,
    'name'            => $_SESSION['name'] ?? null,
    'roles'           => $_SESSION['roles'] ?? [],
    'customer_number' => $_SESSION['customer_number'] ?? null,
    'csrf'            => csrf_token(),
];
$sessionJson = json_encode($session, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// HTML-Template einlesen und Session-Meta-Tags vor </head> einsetzen.
// Vorteil: agenturtool/index.html bleibt als reine UI-Datei unangetastet
// (außer dem klar markierten Patch-Bereich im <script> ab Zeile 2856).
$html = file_get_contents(__DIR__ . '/index.html');
if ($html === false) {
    http_response_code(500);
    echo 'index.html nicht lesbar';
    exit;
}

$inject = "<meta name=\"x-session\" content='" . htmlspecialchars($sessionJson, ENT_QUOTES, 'UTF-8') . "'>\n"
        . "<meta name=\"csrf\"      content='" . htmlspecialchars($session['csrf'], ENT_QUOTES, 'UTF-8') . "'>\n"
        . "<meta name=\"app-base\"  content='" . htmlspecialchars(rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/') . '/', ENT_QUOTES, 'UTF-8') . "'>\n";

// Marker bevorzugt, sonst direkt vor </head> injizieren
if (strpos($html, '<!--SESSION-->') !== false) {
    $html = str_replace('<!--SESSION-->', $inject, $html);
} else {
    $html = preg_replace('#</head>#i', $inject . '</head>', $html, 1);
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
echo $html;
