<?php
declare(strict_types=1);

$slug = 'gutshof-maifest-2026';
$utmSource = isset($_GET['utm_source']) ? trim((string) $_GET['utm_source']) : '';
$utmCampaign = isset($_GET['utm_campaign']) ? trim((string) $_GET['utm_campaign']) : '';

$trackParams = http_build_query([
    'utm_source' => $utmSource,
    'utm_campaign' => $utmCampaign,
]);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Erlebe das Maifest im Gutshof Kassel mit DJ, Weinbar, Streetfood, Live Painting und Kinderprogramm.">
    <title>Maifest im Gutshof Kassel | 1. Mai 2026</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="logo" aria-label="Restaurant Gutshof">
        <span class="logo-top">RESTAURANT</span>
        <span class="logo-bottom">GUTSHOF</span>
    </div>
</header>

<main>
    <section class="hero">
        <p class="eyebrow">Frühlingsevent 2026</p>
        <h1>Maifest im Gutshof Kassel</h1>
        <p class="subheadline">Sonne, Musik, Genuss & Kunst – der perfekte Start in den Mai in besonderer Gutshof-Atmosphäre.</p>
        <p class="event-meta"><strong>1. Mai 2026 · 11:00–19:00 Uhr</strong><br>Wilhelmshöher Allee 347A, Kassel</p>
        <a id="calendarCta" class="btn btn-primary" href="maifest-gutshof-kassel.ics">📅 In Kalender eintragen</a>
    </section>

    <section>
        <h2>Highlights</h2>
        <ul class="chips">
            <li>DJ & Lounge-Sounds</li>
            <li>Weinbar & Maibowle</li>
            <li>Kinderprogramm</li>
        </ul>
    </section>

    <section>
        <h2>Food</h2>
        <div class="card-grid">
            <article class="card"><h3>Tacos</h3><p>Frisch, würzig und perfekt zum entspannten Day-Event.</p></article>
            <article class="card"><h3>Wildschwein-Bratwurst</h3><p>Herzhaft vom Grill, regional inspiriert.</p></article>
            <article class="card"><h3>Grüne Soße</h3><p>Der Klassiker – hausgemacht und saisonal.</p></article>
        </div>
    </section>

    <section>
        <h2>Kunst</h2>
        <p>Live Painting & Custom Art sorgen für kreative Vibes und einzigartige Motive direkt vor Ort.</p>
    </section>

    <section>
        <h2>Event Infos</h2>
        <p><strong>Eintritt:</strong> 5€<br><strong>Kinder bis 14:</strong> frei</p>
        <a class="btn btn-secondary" target="_blank" rel="noopener noreferrer" href="https://maps.google.com/?q=Wilhelmsh%C3%B6her%20Allee%20347A%2C%20Kassel">Route in Google Maps</a>
    </section>
</main>

<a id="stickyCta" class="btn btn-primary sticky-cta" href="maifest-gutshof-kassel.ics">📅 In Kalender eintragen</a>

<footer>
    <p>Wir erfassen anonymisierte Klicks zur Erfolgsmessung.</p>
</footer>

<script>
(function () {
    const slug = <?php echo json_encode($slug, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const trackingQuery = <?php echo json_encode($trackParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function track(eventType) {
        const query = trackingQuery ? '&' + trackingQuery : '';
        const url = 'track.php?event=' + encodeURIComponent(eventType) + query + '&slug=' + encodeURIComponent(slug);

        if (navigator.sendBeacon) {
            return navigator.sendBeacon(url, '');
        }

        return fetch(url, { method: 'GET', credentials: 'same-origin', keepalive: true })
            .then(() => true)
            .catch(() => false);
    }

    track('page_view');

    function handleCalendarClick(event) {
        event.preventDefault();
        const targetHref = event.currentTarget.getAttribute('href') || 'maifest-gutshof-kassel.ics';

        Promise.resolve(track('calendar_click'))
            .catch(() => false)
            .finally(() => {
                window.location.href = targetHref;
            });
    }

    const calendarCta = document.getElementById('calendarCta');
    const stickyCta = document.getElementById('stickyCta');

    if (calendarCta) {
        calendarCta.addEventListener('click', handleCalendarClick);
    }

    if (stickyCta) {
        stickyCta.addEventListener('click', handleCalendarClick);
    }
})();
</script>
</body>
</html>
