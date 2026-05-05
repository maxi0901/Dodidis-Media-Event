<?php $activePage = 'home'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="description" content="Dodidis.Media – Social Media Marketing Agentur. Strategischer Content, der auffällt. Reels, die performen. Kampagnen, die verkaufen.">
    <title>Dodidis.Media – Social Media Marketing Agentur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main id="home">

    <!-- ===== HERO ===== -->
    <section class="hero container">
        <div class="hero-grid">
            <div class="hero-text" data-reveal-stagger>
                <span class="eyebrow">Social Media Marketing Agentur</span>
                <h1 class="hero-headline">
                    Wir produzieren<br>
                    keine Videos.<br>
                    Wir liefern <span class="accent-text">Ergebnisse.</span>
                </h1>
                <p class="hero-sub">
                    Strategischer Content, der auffällt. Reels, die performen.
                    Kampagnen, die verkaufen. Für Brands, die mehr wollen.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="kontakt.php">
                        Erstgespräch vereinbaren
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                    <a class="btn btn-ghost" href="leistungen.php">
                        Mehr erfahren
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                </div>
            </div>

            <div class="hero-visual" data-reveal aria-hidden="true">
                <span class="scene"></span>
                <span class="desk"></span>
                <span class="monitor"></span>
                <span class="silhouette"></span>
            </div>
        </div>
    </section>

    <!-- ===== LEISTUNGEN ===== -->
    <section class="section container" id="leistungen">
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Unsere Leistungen</span>
                <h2>Alles aus einer Hand.</h2>
            </div>
            <p>
                Von der Strategie bis zur Veröffentlichung – wir entwickeln Content,
                der zu deiner Marke passt und deine Zielgruppe erreicht.
            </p>
        </div>

        <div class="services-grid" data-reveal-stagger>
            <a class="service-card" href="leistungen.php#strategie">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>
                </div>
                <h3>Strategie</h3>
                <p>Maßgeschneiderte Strategien für nachhaltiges Wachstum und maximale Reichweite.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>

            <a class="service-card" href="leistungen.php#content">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 6l2-3"/><path d="M13 6l-2-3"/><path d="M17 6l2-3"/></svg>
                </div>
                <h3>Content Creation</h3>
                <p>Reels, Videos &amp; Fotos, die auffallen, begeistern und zum Handeln bewegen.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>

            <a class="service-card" href="leistungen.php#smm">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/></svg>
                </div>
                <h3>Social Media Management</h3>
                <p>Wir übernehmen Plattform, Community &amp; Content – damit du dich um nichts kümmern musst.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>

            <a class="service-card" href="leistungen.php#performance">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22 22 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
                </div>
                <h3>Performance Marketing</h3>
                <p>Gezielte Kampagnen, die nicht nur Reichweite bringen, sondern Ergebnisse liefern.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>

        <!-- Stats -->
        <div class="stats" aria-label="Unsere Zahlen" data-reveal-stagger>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                </span>
                <div>
                    <div class="stat-num">30+</div>
                    <div class="stat-label">Projekte<br>abgeschlossen</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                </span>
                <div>
                    <div class="stat-num">15+</div>
                    <div class="stat-label">zufriedene<br>Kunden</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <div>
                    <div class="stat-num">2.5M+</div>
                    <div class="stat-label">erreichte<br>Konten</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
                <div>
                    <div class="stat-num">98%</div>
                    <div class="stat-label">Kunden<br>zufriedenheit</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PROJEKTE ===== -->
    <section class="section container" id="projekte">
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Ausgewählte Projekte</span>
                <h2>Echte Ergebnisse. Echte Brands.</h2>
            </div>
            <a class="ghost-link head-action" href="projekte.php">
                Alle Projekte ansehen
                <span aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>

        <div class="projects-grid" data-reveal-stagger>
            <a class="project project-1" href="projekte.php#launch-night-kassel">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Social Media &amp; Reels</span>
            </a>
            <a class="project project-2" href="projekte.php#event-content">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Event Content</span>
            </a>
            <a class="project project-3" href="projekte.php#video-production">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Video Production</span>
            </a>
            <a class="project project-4" href="projekte.php#ads-vb">
                <div class="img" aria-hidden="true"></div>
                <span class="vendor">VB</span>
                <span class="label">Social Media &amp; Ads</span>
            </a>

            <a class="icon-btn projects-nav" href="projekte.php" aria-label="Weitere Projekte">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
            </a>
        </div>
    </section>

    <!-- ===== TESTIMONIAL ===== -->
    <section class="section container" id="ueber-uns">
        <div class="testimonial" data-reveal>
            <div>
                <div class="quote-mark" aria-hidden="true">&ldquo;</div>
                <h2>Das sagen<br>unsere Kunden.</h2>
            </div>
            <div>
                <div class="testimonial-slide is-active">
                    <p class="testimonial-quote">
                        „Die Zusammenarbeit mit Dodidis.Media hat unseren Social Media Auftritt
                        auf ein neues Level gebracht. Kreativ, zuverlässig und immer einen
                        Schritt voraus."
                    </p>
                    <div class="testimonial-row">
                        <div class="testimonial-author">
                            <div class="author-pic" aria-hidden="true">M</div>
                            <div class="author-meta">
                                <div class="author-name">Marvin Becker</div>
                                <div class="author-role">Gründer von Becker Fitness</div>
                            </div>
                        </div>
                        <div class="testimonial-controls">
                            <button class="icon-btn" type="button" data-testimonial-prev aria-label="Vorheriges Testimonial">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M11 19l-7-7 7-7"/></svg>
                            </button>
                            <button class="icon-btn" type="button" data-testimonial-next aria-label="Nächstes Testimonial">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="testimonial-slide">
                    <p class="testimonial-quote">
                        „Schnelle Umsetzung, klare Kommunikation und Reels, die wirklich performen.
                        Wir konnten unsere Reichweite in wenigen Wochen vervielfachen."
                    </p>
                    <div class="testimonial-row">
                        <div class="testimonial-author">
                            <div class="author-pic" aria-hidden="true">L</div>
                            <div class="author-meta">
                                <div class="author-name">Lara Schneider</div>
                                <div class="author-role">Marketing Lead, Studio Nord</div>
                            </div>
                        </div>
                        <div class="testimonial-controls">
                            <button class="icon-btn" type="button" data-testimonial-prev aria-label="Vorheriges Testimonial">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M11 19l-7-7 7-7"/></svg>
                            </button>
                            <button class="icon-btn" type="button" data-testimonial-next aria-label="Nächstes Testimonial">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="testimonial-slide">
                    <p class="testimonial-quote">
                        „Endlich ein Partner, der nicht nur Content liefert, sondern strategisch
                        mitdenkt. Die Resultate sprechen für sich – mehr Leads, mehr Umsatz."
                    </p>
                    <div class="testimonial-row">
                        <div class="testimonial-author">
                            <div class="author-pic" aria-hidden="true">T</div>
                            <div class="author-meta">
                                <div class="author-name">Tim Vogel</div>
                                <div class="author-role">Inhaber, Vogel &amp; Becker GmbH</div>
                            </div>
                        </div>
                        <div class="testimonial-controls">
                            <button class="icon-btn" type="button" data-testimonial-prev aria-label="Vorheriges Testimonial">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M11 19l-7-7 7-7"/></svg>
                            </button>
                            <button class="icon-btn" type="button" data-testimonial-next aria-label="Nächstes Testimonial">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="assets/site.js"></script>
</body>
</html>
