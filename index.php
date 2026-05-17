<?php $activePage = 'home'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0E1422">
    <meta name="description" content="Dodidis.Media – Wir sorgen dafür, dass deine Marke wahrgenommen wird. Sichtbarkeit, Reichweite und Vertrauen über Social Media – damit du dich auf dein Kerngeschäft konzentrieren kannst.">
    <title>Dodidis.Media – Sichtbarkeit, die in Anfragen mündet.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main id="home">

    <!-- ===== HERO ===== -->
    <section class="hero container hero-v3 hero-centered" id="hero" data-hero-parallax>
        <div class="hero-content">
            <div class="hero-text hero-text-centered">
                <span class="eyebrow eyebrow-pill hero-reveal" style="--reveal-i:0">Content ohne Aufwand</span>
                <h1 class="hero-headline-xl hero-headline-stack">
                    <span class="hero-line hero-reveal" style="--reveal-i:1">Wir machen keine Videos.</span>
                    <span class="hero-line hero-reveal" style="--reveal-i:2"><span class="accent-text">Wir liefern Ergebnisse.</span></span>
                </h1>
                <p class="hero-sub hero-reveal" style="--reveal-i:3">
                    Reels und Social Content, die Reichweite, Vertrauen und Kundenanfragen bringen –
                    damit du dich auf dein Kerngeschäft konzentrieren kannst.
                </p>
                <div class="hero-actions hero-actions-centered hero-reveal" style="--reveal-i:4">
                    <a class="btn btn-primary" href="#kontakt">
                        Erstgespräch buchen
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                    <a class="btn btn-ghost" href="#case-studies">
                        Unsere Projekte
                    </a>
                </div>
            </div>

            <div class="hero-founders-stage hero-reveal" style="--reveal-i:5" data-hero-image>
                <div class="hero-founders-glow" aria-hidden="true" data-hero-bg></div>
                <img class="hero-founders-cutout" src="assets/hero-founders.svg" alt="Gründer von Dodidis.Media" width="986" height="1148" loading="eager" decoding="async" data-hero-fg>
                <div class="hero-founders-floor" aria-hidden="true"></div>
            </div>

            <div class="hero-trust-row hero-reveal" style="--reveal-i:6" aria-label="Vertrauensindikatoren">
                <span class="hero-trust-item"><strong>30+</strong> Projekte</span>
                <span class="hero-trust-item"><strong>2,5 Mio+</strong> Reichweite</span>
                <span class="hero-trust-item"><strong>98 %</strong> Kundenzufriedenheit</span>
            </div>

            <div class="hero-tagline-pill hero-reveal" style="--reveal-i:7" aria-hidden="true">
                <span class="hero-tagline-dot"></span>
                Minimaler Aufwand. Maximale Performance.
            </div>
        </div>
    </section>

    <!-- ===== LOGOS / VERTRAUEN ===== -->
    <section class="section logos-band" id="logos" aria-label="Marken & Kundenlogos">
        <div class="container">
            <p class="logos-eyebrow">Vertrauen aus der Praxis</p>
            <!--
                Logo-Marquee — Slot für echte Kundenlogos.
                Tausche assets/logos/kunde-XX.svg gegen die echten Logo-Dateien aus.
                Eine Zeile pro Logo, JS klont das Set automatisch für nahtlosen Loop.
            -->
            <div class="logos-marquee" data-logos-marquee>
                <div class="logos-track">
                    <span class="logo-item"><img src="assets/logos/kunde-01.svg" alt="Northgate"></span>
                    <span class="logo-item"><img src="assets/logos/kunde-02.svg" alt="Atlas &amp; Co."></span>
                    <span class="logo-item"><img src="assets/logos/kunde-03.svg" alt="Verify Labs"></span>
                    <span class="logo-item"><img src="assets/logos/kunde-04.svg" alt="Hofgut Nord"></span>
                    <span class="logo-item"><img src="assets/logos/kunde-05.svg" alt="Delta Studio"></span>
                    <span class="logo-item"><img src="assets/logos/kunde-06.svg" alt="Meridian"></span>
                    <span class="logo-item"><img src="assets/logos/kunde-07.svg" alt="Becker Fit"></span>
                    <span class="logo-item"><img src="assets/logos/kunde-08.svg" alt="Vogel &amp; Co."></span>
                    <span class="logo-item"><img src="assets/logos/kunde-09.svg" alt="Aurora Health"></span>
                    <span class="logo-item"><img src="assets/logos/kunde-10.svg" alt="Solaris Brand"></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PAINPOINT ===== -->
    <section class="section container" id="painpoint">
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Das Problem</span>
                <h2 class="painpoint-headline" data-reveal-lines>
                    <span class="reveal-line"><span style="--reveal-index:0">Das Problem ist nicht dein Produkt.</span></span>
                    <span class="reveal-line accent-text"><span style="--reveal-index:1">Das Problem ist, dass dich niemand wahrnimmt.</span></span>
                </h2>
            </div>
            <p data-reveal-soft style="--reveal-delay:200ms">
                Ob du gerade erst startest oder schon postest – ohne klare Sichtbarkeit
                bleibt selbst die beste Leistung im digitalen Hintergrundrauschen.
            </p>
        </div>

        <div class="painpoint-grid">
            <article class="painpoint-card is-left" data-reveal>
                <span class="painpoint-eyebrow">Szenario 1</span>
                <h3>Du machst noch kein Social Media.</h3>
                <ul class="painpoint-list">
                    <li>Keine Zeit neben dem Tagesgeschäft</li>
                    <li>Keine Ahnung, was wirklich funktioniert</li>
                    <li>Plattformen ändern sich gefühlt im Wochentakt</li>
                    <li>Du kommst dem Tempo nicht hinterher</li>
                    <li>Unsicherheit, was du überhaupt zeigen sollst</li>
                    <li>Die Konkurrenz wird täglich sichtbarer</li>
                </ul>
                <div class="painpoint-visual" aria-hidden="true">
                    <span class="painpoint-chip">📱 Notification</span>
                    <span class="painpoint-chip">#trending</span>
                    <span class="painpoint-chip">Reels?</span>
                    <span class="painpoint-chip">Algorithmus</span>
                    <span class="painpoint-chip">TikTok</span>
                    <span class="painpoint-chip">Hooks</span>
                    <span class="painpoint-chip">Posten?</span>
                    <span class="painpoint-chip">📈 Analytics</span>
                </div>
            </article>

            <article class="painpoint-card is-right" data-reveal>
                <span class="painpoint-eyebrow">Szenario 2</span>
                <h3>Du machst bereits Social Media.</h3>
                <ul class="painpoint-list">
                    <li>Kaum Reichweite trotz regelmäßigem Posten</li>
                    <li>Falsche Zielgruppe, falsche Erwartungen</li>
                    <li>Keine echten Kundenanfragen</li>
                    <li>Keine Strategie, nur Bauchgefühl</li>
                    <li>Content ohne Wirkung – Likes ≠ Umsatz</li>
                    <li>Stunden investiert, ohne messbares Ergebnis</li>
                </ul>
                <div class="painpoint-visual" aria-hidden="true">
                    <svg class="painpoint-chart" data-chart-draw viewBox="0 0 320 100" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <!-- Sinkende Kurve -->
                        <path class="chart-line" data-draw-path d="M0 30 L40 25 L80 40 L120 35 L160 55 L200 60 L240 70 L280 78 L320 85" stroke="#46A99A" opacity="0.55"/>
                        <!-- Datenpunkte -->
                        <circle class="chart-dot" cx="40"  cy="25" r="3" fill="#6CD3C1"/>
                        <circle class="chart-dot" cx="120" cy="35" r="3" fill="#46A99A"/>
                        <circle class="chart-dot" cx="200" cy="60" r="3" fill="#358F81" opacity="0.7"/>
                        <circle class="chart-dot" cx="280" cy="78" r="3" fill="#358F81" opacity="0.5"/>
                        <!-- Achse -->
                        <line x1="0" y1="98" x2="320" y2="98" stroke="rgba(255,255,255,0.08)"/>
                    </svg>
                </div>
            </article>
        </div>
    </section>

    <!-- ===== HEBEL / LÖSUNG ===== -->
    <section class="section hebel-section" id="hebel">
        <div class="container">
            <div class="section-head" data-reveal>
                <div class="lead">
                    <span class="eyebrow">Die Lösung</span>
                    <h2 data-reveal-lines>
                        <span class="reveal-line"><span style="--reveal-index:0">Social Media ist</span></span>
                        <span class="reveal-line"><span style="--reveal-index:1"><span class="accent-text">der unsichtbare Hebel</span>.</span></span>
                    </h2>
                </div>
                <p data-reveal-soft style="--reveal-delay:220ms">
                    Wer ihn richtig ansetzt, hebt sich aus dem Rauschen heraus.
                    Wer ihn ignoriert, bleibt im Hintergrund – egal, wie gut die Arbeit ist.
                </p>
            </div>

            <div class="hebel-stage" data-hebel-stage data-reveal-scale aria-hidden="true">
                <svg class="hebel-svg" viewBox="0 0 800 400" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="beamGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#1F2A40"/>
                            <stop offset="50%" stop-color="#46A99A"/>
                            <stop offset="100%" stop-color="#6CD3C1"/>
                        </linearGradient>
                        <radialGradient id="glowGrad" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#6CD3C1" stop-opacity="0.7"/>
                            <stop offset="100%" stop-color="#6CD3C1" stop-opacity="0"/>
                        </radialGradient>
                    </defs>

                    <!-- Glow under right side (grows with --lever-glow) -->
                    <ellipse cx="640" cy="220" rx="160" ry="60"
                             fill="url(#glowGrad)"
                             opacity="calc(var(--lever-glow, 0) * 0.85)"
                             style="transition: opacity 0.4s ease;"/>

                    <!-- Fulcrum (Drehpunkt) -->
                    <polygon points="400,260 360,360 440,360" fill="#1A2438" stroke="#46A99A" stroke-width="1.5"/>
                    <circle cx="400" cy="260" r="6" fill="#6CD3C1" stroke="#0E1422" stroke-width="2"/>

                    <!-- Beam (rotates with --lever-tilt) -->
                    <g class="hebel-beam">
                        <rect x="120" y="252" width="560" height="14" rx="7" fill="url(#beamGrad)" stroke="rgba(108,211,193,0.4)" stroke-width="0.6"/>

                        <!-- LEFT LOAD: heavy, "Unsichtbarkeit" -->
                        <g class="hebel-load-left" transform="translate(180, 200)">
                            <rect x="-50" y="-30" width="100" height="56" rx="10" fill="#101A30" stroke="rgba(255,255,255,0.10)" stroke-width="1"/>
                            <text x="0" y="-6" text-anchor="middle" class="hebel-side-label" fill="#F5F8FF">Unsichtbarkeit</text>
                            <text x="0" y="14" text-anchor="middle" class="hebel-side-sub" fill="rgba(245,248,255,0.55)">wenig Anfragen</text>
                        </g>

                        <!-- RIGHT LOAD: light, lifted "Reichweite + Vertrauen + Anfragen" -->
                        <g class="hebel-load-right" transform="translate(620, 200)">
                            <rect x="-66" y="-44" width="132" height="76" rx="12" fill="#13243A" stroke="rgba(108,211,193,0.5)" stroke-width="1.2"/>
                            <text x="0" y="-22" text-anchor="middle" class="hebel-side-label" fill="#6CD3C1">Reichweite</text>
                            <text x="0" y="-2"  text-anchor="middle" class="hebel-side-label" fill="#F5F8FF">Vertrauen</text>
                            <text x="0" y="20"  text-anchor="middle" class="hebel-side-label" fill="#F5F8FF">Kundenanfragen</text>
                        </g>

                        <!-- Cables -->
                        <line x1="180" y1="252" x2="180" y2="226" stroke="rgba(255,255,255,0.20)" stroke-width="1.2"/>
                        <line x1="620" y1="252" x2="620" y2="220" stroke="rgba(108,211,193,0.45)" stroke-width="1.4"/>
                    </g>

                    <!-- Floating particles around right side -->
                    <g>
                        <circle class="hebel-particle" cx="660" cy="180" r="3" fill="#6CD3C1"/>
                        <circle class="hebel-particle" cx="700" cy="150" r="2" fill="#46A99A"/>
                        <circle class="hebel-particle" cx="610" cy="140" r="2.5" fill="#6CD3C1"/>
                        <circle class="hebel-particle" cx="730" cy="200" r="2" fill="#358F81"/>
                        <circle class="hebel-particle" cx="580" cy="170" r="1.8" fill="#6CD3C1"/>
                        <circle class="hebel-particle" cx="690" cy="220" r="2.2" fill="#46A99A"/>
                    </g>
                </svg>
            </div>

            <div class="hebel-text" data-reveal-stagger>
                <span class="hebel-line">Nicht jeder Unternehmer sollte Videograf werden müssen.</span>
                <span class="hebel-line">Ein Koch sollte kochen.</span>
                <span class="hebel-line">Ein Arzt sollte behandeln.</span>
                <span class="hebel-line">Ein Handwerker sollte bauen.</span>
                <span class="hebel-line hebel-final">Wir übernehmen den Rest.</span>
            </div>
        </div>
    </section>

    <!-- ===== LEISTUNGEN ===== -->
    <section class="section container" id="leistungen">
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Was wir wirklich liefern</span>
                <h2>Strategie, Content, Distribution, Performance – <span class="accent-text">aus einer Hand</span>.</h2>
            </div>
            <p>
                Statt einzelner Bausteine bekommst du das Gesamtpaket: alles was nötig ist,
                damit deine Marke gesehen wird und Anfragen ankommen.
            </p>
        </div>

        <div class="services-grid" data-reveal-stagger>
            <a class="service-card" href="#strategie">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>
                </div>
                <h3>Strategie</h3>
                <p>Maßgeschneiderte Strategien für nachhaltiges Wachstum und maximale Reichweite.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>

            <a class="service-card" href="#content">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 6l2-3"/><path d="M13 6l-2-3"/><path d="M17 6l2-3"/></svg>
                </div>
                <h3>Content Creation</h3>
                <p>Reels, Videos &amp; Fotos, die auffallen, begeistern und zum Handeln bewegen.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>

            <a class="service-card" href="#smm">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/></svg>
                </div>
                <h3>Social Media Management</h3>
                <p>Wir übernehmen Plattform, Community &amp; Content – damit du dich um nichts kümmern musst.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>

            <a class="service-card" href="#performance">
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

        <div class="detail-grid" data-reveal-stagger>
            <article class="detail-card" id="strategie">
                <h3>Strategie</h3>
                <p>Wir bauen eine belastbare Grundlage mit Zielgruppen-Analyse, Positionierung und klarer Content-Roadmap.</p>
                <ul>
                    <li>Mehr Klarheit in der Kommunikation</li>
                    <li>Höhere Trefferquote bei Kampagnen</li>
                    <li>Bessere Conversion über alle Kanäle</li>
                </ul>
            </article>
            <article class="detail-card" id="content">
                <h3>Content Creation</h3>
                <p>Produktion mit Fokus auf Wirkung: starke Hooks, klare Story und visuelle Wiedererkennbarkeit.</p>
                <ul>
                    <li>Mehr Reichweite durch starke Reels</li>
                    <li>Höhere Watchtime &amp; Interaktion</li>
                    <li>Professioneller Brand-Auftritt</li>
                </ul>
            </article>
            <article class="detail-card" id="smm">
                <h3>Social Media Management</h3>
                <p>Wir steuern deinen Auftritt täglich und sorgen für konsistente Veröffentlichung und Community-Nähe.</p>
                <ul>
                    <li>Regelmäßiger, hochwertiger Output</li>
                    <li>Messbare Kontinuität</li>
                    <li>Mehr Vertrauen bei deiner Zielgruppe</li>
                </ul>
            </article>
            <article class="detail-card" id="performance">
                <h3>Performance Marketing</h3>
                <p>Von Setup bis Skalierung: Kampagnen werden laufend getestet und auf Profitabilität optimiert.</p>
                <ul>
                    <li>Mehr qualifizierte Leads</li>
                    <li>Effizienterer Ad-Spend</li>
                    <li>Steigender Umsatz</li>
                </ul>
            </article>
        </div>

        <!-- Stats -->
        <div class="stats" aria-label="Unsere Zahlen" data-reveal-stagger>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                </span>
                <div>
                    <div class="stat-num" data-count-up="30" data-count-suffix="+">30+</div>
                    <div class="stat-label">Projekte<br>abgeschlossen</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                </span>
                <div>
                    <div class="stat-num" data-count-up="15" data-count-suffix="+">15+</div>
                    <div class="stat-label">zufriedene<br>Kunden</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <div>
                    <div class="stat-num" data-count-up="2.5" data-count-decimals="1" data-count-suffix="M+">2.5M+</div>
                    <div class="stat-label">erreichte<br>Konten</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
                <div>
                    <div class="stat-num" data-count-up="98" data-count-suffix="%">98%</div>
                    <div class="stat-label">Kunden<br>zufriedenheit</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SCROLL TEXT BANNER (Outline + Solid) ===== -->
    <section class="scroll-text-banner" data-scroll-banner aria-hidden="true">
        <div class="scroll-text-banner-inner">
            <span class="scroll-text-emoji scroll-text-emoji-1">🤩</span>
            <span class="scroll-text-emoji scroll-text-emoji-2">🚀</span>
            <span class="scroll-text-line scroll-text-outline" data-banner-line="left">Von unsichtbar</span>
            <span class="scroll-text-line scroll-text-solid"   data-banner-line="right">zu ausgebucht</span>
        </div>
    </section>

    <!-- ===== ERGEBNISSE / CASE STUDIES ===== -->
    <section class="section container" id="case-studies">
        <span id="projekte" aria-hidden="true" style="position:absolute"></span>
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Ergebnisse</span>
                <h2>Sichtbarkeit, die in <span class="accent-text">Anfragen</span> mündet.</h2>
            </div>
            <p>
                Vergiss leere Versprechen. Wir liefern messbare Resultate –
                in Reichweite, Vertrauen und Kundenanfragen.
            </p>
        </div>

        <div class="testimonial" data-reveal>
            <div>
                <div class="quote-mark" aria-hidden="true">&ldquo;</div>
                <h2>Case Study<br>Healthcare.</h2>
            </div>
            <div>
                <p class="testimonial-quote">
                    <strong>Problem:</strong> Unklare Botschaft und sinkende Performance.<br>
                    <strong>Lösung:</strong> Neue Positionierung, 12 Reels/Monat &amp; eine datenbasierte Ad-Struktur.<br>
                    <strong>Ergebnis:</strong> <span class="result-num" data-count-up="168" data-count-prefix="+" data-count-suffix="%">+168%</span> Reichweite und <span class="result-num" data-count-up="42" data-count-prefix="+" data-count-suffix="%">+42%</span> qualifizierte Leads in nur 8 Wochen.
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
    </section>

    <!-- ===== ÜBER UNS ===== -->
    <section class="section container" id="ueber-uns">
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Über uns</span>
                <h2>Zwei Köpfe, ein Ziel: <span class="accent-text">deine Sichtbarkeit</span>.</h2>
            </div>
            <p>
                Junges Team aus Nordhessen mit klarem Fokus auf messbare Wirkung.
                Kurze Wege, schnelle Entscheidungen, ehrliche Beratung – kein Agentur-Theater.
            </p>
        </div>

        <div class="about-grid" data-reveal-stagger>
            <article class="about-card">
                <h3>Wer wir sind</h3>
                <p>
                    Ein kleines Kernteam aus Strateg:innen, Creator:innen und Marketern,
                    das Social Media als Werkzeug versteht – nicht als Bühne.
                </p>
            </article>
            <article class="about-card">
                <h3>Arbeitsweise</h3>
                <ul>
                    <li>Strategie: Ziele, Zielgruppe, Messaging</li>
                    <li>Umsetzung: Content, Ads, Distribution</li>
                    <li>Optimierung: Testing, Tracking, Skalierung</li>
                </ul>
            </article>
        </div>
    </section>

    <!-- ===== TESTIMONIALS / STIMMEN ===== -->
    <section class="section testimonials-section container" id="stimmen">
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Stimmen</span>
                <h2>Mehr als <span class="accent-text">nur Zahlen</span>.</h2>
            </div>
            <p>
                Was unsere Kunden über die Zusammenarbeit sagen – und warum sie immer wiederkommen.
            </p>
        </div>

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

    <!-- ===== KONTAKT ===== -->
    <section class="section container" id="kontakt">
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Erstgespräch</span>
                <h2>Lass uns deine <span class="accent-text">Sichtbarkeit</span> sortieren.</h2>
            </div>
            <p>
                In 30 Minuten klären wir Potenziale, Prioritäten und die nächsten konkreten Schritte –
                unverbindlich, ehrlich und ohne Verkaufs-Pitch.
            </p>
        </div>

        <div class="contact-grid" data-reveal>
            <div class="contact-card">
                <h3>Warum ein Erstgespräch?</h3>
                <p>30 Minuten, in denen wir Potenziale, Prioritäten und Schritte sortieren.</p>
                <ul>
                    <li>Klare Strategie-Empfehlung</li>
                    <li>Realistische Zeitplanung</li>
                    <li>Direkter Maßnahmenplan</li>
                </ul>

                <ul class="contact-meta-list">
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <a href="mailto:hallo@dodidis-media.de">hallo@dodidis-media.de</a>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <a href="tel:+4917660172907">+49 176 60172907</a>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>Nordhessen, Deutschland</span>
                    </li>
                </ul>
            </div>

            <div class="contact-form-card">
                <form novalidate>
                    <label>
                        Name
                        <input type="text" name="name" autocomplete="name" required>
                    </label>
                    <label>
                        E-Mail
                        <input type="email" name="email" autocomplete="email" required>
                    </label>
                    <label>
                        Unternehmen <span style="color: var(--text-muted); font-weight: 500;">(optional)</span>
                        <input type="text" name="company" autocomplete="organization">
                    </label>
                    <label>
                        Nachricht
                        <textarea name="message" rows="5" required></textarea>
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="consent" required>
                        <span>Ich willige ein, dass meine Angaben zur Bearbeitung meiner Anfrage verwendet werden. Mehr in der <a href="datenschutz.php" style="color: var(--accent);">Datenschutzerklärung</a>.</span>
                    </label>
                    <button type="submit" class="btn btn-primary">
                        Erstgespräch anfragen
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </button>
                    <p class="form-status" role="status" aria-live="polite"></p>
                </form>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="assets/site.js"></script>
</body>
</html>
