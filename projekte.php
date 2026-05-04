<?php $activePage = 'projekte'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="description" content="Projekte von Dodidis.Media – Event Content, Reels, Performance-Kampagnen und Brand Auftritte.">
    <title>Projekte | Dodidis.Media</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main>
    <section class="page-hero container reveal">
        <span class="eyebrow">Ausgewählte Projekte</span>
        <h1>Echte Ergebnisse. Echte Brands.</h1>
        <p class="lead-sub">
            Ausgewählte Produktionen aus Event Content, Reels und Performance-Kampagnen –
            für Marken, die mehr als nur schöne Bilder wollen.
        </p>
    </section>

    <section class="section container reveal">
        <div class="projects-grid">
            <a class="project project-1" href="#launch-night-kassel" id="launch-night-kassel">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Event Content</span>
            </a>
            <a class="project project-2" href="#event-content" id="event-content">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Lead Funnel</span>
            </a>
            <a class="project project-3" href="#video-production" id="video-production">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Reels</span>
            </a>
            <a class="project project-4" href="#ads-vb" id="ads-vb">
                <div class="img" aria-hidden="true"></div>
                <span class="vendor">VB</span>
                <span class="label">Social Media &amp; Ads</span>
            </a>
            <a class="project project-5" href="lp/gutshof-maifest-2026/">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Maifest 2026</span>
            </a>
            <a class="project project-6" href="#brand-launch">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Brand Launch</span>
            </a>
            <a class="project project-7" href="#restaurant-series">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Restaurant Series</span>
            </a>
            <a class="project project-8" href="#ecom-q4">
                <div class="img" aria-hidden="true"></div>
                <span class="label">E-Commerce Scale</span>
            </a>
        </div>
    </section>

    <section class="section container reveal">
        <div class="testimonial">
            <div>
                <div class="quote-mark" aria-hidden="true">&ldquo;</div>
                <h2>Case Study<br>Healthcare.</h2>
            </div>
            <div>
                <p class="testimonial-quote">
                    <strong>Problem:</strong> Unklare Botschaft und sinkende Performance.<br>
                    <strong>Lösung:</strong> Neue Positionierung, 12 Reels/Monat &amp; eine datenbasierte Ad-Struktur.<br>
                    <strong>Ergebnis:</strong> +168% Reichweite und +42% qualifizierte Leads in nur 8 Wochen.
                </p>
                <div class="testimonial-row">
                    <div class="testimonial-author">
                        <div class="author-pic" aria-hidden="true">H</div>
                        <div class="author-meta">
                            <div class="author-name">Healthcare Brand</div>
                            <div class="author-role">8 Wochen Performance Sprint</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cta-band reveal">
            <div>
                <h2>Dein Projekt als nächste Case Study?</h2>
                <p>Wir hören zu, prüfen Potenziale und liefern eine ehrliche Einschätzung.</p>
            </div>
            <a class="btn btn-primary" href="kontakt.php">
                Projekt anfragen
                <span class="arrow" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="assets/site.js"></script>
</body>
</html>
