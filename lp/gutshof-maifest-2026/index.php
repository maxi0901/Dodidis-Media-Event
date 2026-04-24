<?php
declare(strict_types=1);

$utmSource = isset($_GET['utm_source']) ? trim((string) $_GET['utm_source']) : '';
$utmCampaign = isset($_GET['utm_campaign']) ? trim((string) $_GET['utm_campaign']) : '';
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
        <span class="hero-badge">Feiert mit uns in den Mai!</span>
        <p class="eyebrow">Frühlingsevent 2026</p>
        <h1>Maifest im<br>Gutshof Kassel</h1>
        <p class="subheadline">Sonne, Musik, Kulinarik & Kunst – der stilvolle Start in den Mai in besonderer Gutshof-Atmosphäre.</p>

        <div class="event-info-bar" aria-label="Veranstaltungsdetails">
            <p><span>📅</span> 1. Mai 2026</p>
            <p><span>🕚</span> 11:00–19:00 Uhr</p>
            <p><span>📍</span> Wilhelmshöher Allee 347A, Kassel</p>
        </div>

        <a id="calendarCta" class="btn btn-primary track-calendar-cta" href="maifest-gutshof-kassel.ics">📅 1. Mai vormerken</a>
    </section>

    <section class="editorial-grid" aria-label="Editorial Highlights">
        <article class="editorial-card">
            <p class="editorial-label">Kulinarik</p>
            <h2>Taqueria & Wildschwein</h2>
            <p>Liebevoll zubereitete Tacos, Grüne Soße, regionale Wildschwein-Bratwurst, Kaffee &amp; Blechkuchen.</p>
        </article>
        <article class="editorial-card">
            <p class="editorial-label">Highlight</p>
            <h2>Live Painting & Custom Art</h2>
            <p>Künstler Shary, bekannt aus den Medien, fertigt vor Ort individuelle Designs auf Handyhüllen &amp; Accessoires.</p>
        </article>
    </section>

    <section>
        <h2>Highlights</h2>
        <div class="highlight-grid" aria-label="Event Highlights">
            <article class="highlight-card"><p class="icon">🎧</p><h3>DJ &amp; Lounge-Sounds</h3><p>Entspanntes Day-Event mit eleganten Vibes.</p></article>
            <article class="highlight-card"><p class="icon">🍷</p><h3>Weinbar &amp; Maibowle</h3><p>Frisch gemixt und perfekt für den Frühlingsstart.</p></article>
            <article class="highlight-card"><p class="icon">🌮</p><h3>Tacos</h3><p>Frisch, würzig und mit Liebe zubereitet.</p></article>
            <article class="highlight-card"><p class="icon">🔥</p><h3>Wildschwein-Bratwurst</h3><p>Regional inspiriert und herzhaft vom Grill.</p></article>
            <article class="highlight-card"><p class="icon">🎨</p><h3>Live Painting</h3><p>Vor Ort entstehen persönliche Custom-Motive.</p></article>
            <article class="highlight-card"><p class="icon">🧒</p><h3>Kinderprogramm</h3><p>Spiel, Spaß und kreative Aktivitäten den ganzen Tag.</p></article>
        </div>
    </section>

    <section class="infos">
        <h2>Event Infos</h2>
        <p><strong>Eintritt:</strong> 5€ · <strong>Kinder bis 14:</strong> frei</p>
        <a id="finalCta" class="btn btn-primary track-calendar-cta" href="maifest-gutshof-kassel.ics">📅 Jetzt in Kalender speichern</a>
        <a class="btn btn-secondary" target="_blank" rel="noopener noreferrer" href="https://maps.google.com/?q=Wilhelmsh%C3%B6her%20Allee%20347A%2C%20Kassel">Route in Google Maps</a>
    </section>
</main>

<a id="stickyCta" class="btn btn-primary sticky-cta track-calendar-cta" href="maifest-gutshof-kassel.ics">📅 1. Mai vormerken</a>

<footer>
    <p>Wir erfassen anonymisierte Klicks zur Erfolgsmessung.</p>
</footer>

<script>
(function () {
    const utmSource = <?php echo json_encode($utmSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const utmCampaign = <?php echo json_encode($utmCampaign, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function buildTrackUrl(eventType) {
        const params = new URLSearchParams();
        params.set('event', eventType);
        if (utmSource) {
            params.set('source', utmSource);
        }
        if (utmCampaign) {
            params.set('campaign', utmCampaign);
        }
        return 'track.php?' + params.toString();
    }

    function trackEvent(eventType) {
        const url = buildTrackUrl(eventType);

        if (navigator.sendBeacon) {
            const queued = navigator.sendBeacon(url);
            if (queued) {
                return Promise.resolve(true);
            }
        }

        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            keepalive: true,
            cache: 'no-store'
        }).then(function () {
            return true;
        }).catch(function () {
            return false;
        });
    }

    trackEvent('page_view');

    function handleCalendarClick(event) {
        event.preventDefault();

        const targetHref = event.currentTarget.getAttribute('href') || 'maifest-gutshof-kassel.ics';
        let didNavigate = false;

        function navigateToIcs() {
            if (didNavigate) {
                return;
            }
            didNavigate = true;
            window.location.href = targetHref;
        }

        const timeoutId = window.setTimeout(navigateToIcs, 300);

        Promise.resolve(trackEvent('calendar_click'))
            .catch(function () {
                return false;
            })
            .finally(function () {
                window.clearTimeout(timeoutId);
                navigateToIcs();
            });
    }

    document.querySelectorAll('.track-calendar-cta').forEach(function (ctaButton) {
        ctaButton.addEventListener('click', handleCalendarClick, { passive: false });
    });
})();
</script>
</body>
</html>
