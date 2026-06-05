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
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/assets/style.css') ?>">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main id="home">

    <!-- ===== HERO ===== -->
    <section class="hero container hero-v3 hero-centered" id="hero" data-hero-parallax>
        <div class="hero-content">
            <div class="hero-text hero-text-centered">
                <h1 class="hero-headline-xl hero-headline-stack">
                    <span class="hero-line hero-reveal" style="--reveal-i:0">Wir machen keine Videos.</span>
                    <span class="hero-line hero-reveal" style="--reveal-i:1"><span class="accent-text">Wir liefern Ergebnisse.</span></span>
                </h1>
                <p class="hero-sub hero-reveal" style="--reveal-i:2">
                    Reels und Social Content, die Reichweite, Vertrauen und Kundenanfragen bringen,
                    damit du dich auf dein Kerngeschäft konzentrieren kannst.
                </p>
                <div class="hero-actions hero-actions-centered hero-reveal" style="--reveal-i:3">
                    <a class="btn btn-primary" href="#kontakt">
                        Kostenloses Erstgespräch buchen
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                    <a class="btn btn-ghost" href="#leistungen">
                        Unsere Leistungen
                    </a>
                </div>
            </div>

            <div class="hero-founders-stage" data-hero-image>
                <div class="hero-founders-glow" aria-hidden="true" data-hero-bg></div>
                <picture>
                    <source srcset="assets/hero-founders.webp" type="image/webp">
                    <img class="hero-founders-cutout" src="assets/hero-founders.png" alt="Gründer von Dodidis.Media" width="1100" height="1418" loading="eager" decoding="async" fetchpriority="high" data-hero-fg>
                </picture>
                <div class="hero-founders-floor" aria-hidden="true"></div>
            </div>

        </div>
    </section>

    <!-- ===== LOGOS / VERTRAUEN ===== -->
    <section class="section logos-band" id="logos" aria-label="Marken & Kundenlogos">
        <div class="container">
            <p class="logos-eyebrow">Vertrauen aus der Praxis</p>
            <!--
                Logo-Marquee — echte Kundenlogos.
                Lade die Logo-Dateien als assets/logos/logo-1.png ... logo-4.png hoch
                (1 Zinzino, 2 Asklepios, 3 BLU Guxhagen, 4 SOLA Festival).
                Jedes Bildformat ist ok, einfach so benennen. Das JS füllt die
                Breite automatisch auf und klont für den nahtlosen Endlos-Loop.
                Weitere Logos später einfach als zusätzliche .logo-item ergänzen.
            -->
            <div class="logos-marquee" data-logos-marquee>
                <div class="logos-track">
                    <span class="logo-item"><img src="assets/logos/logo-1.png" alt="Zinzino"></span>
                    <span class="logo-item"><img src="assets/logos/logo-2.png" alt="Asklepios Kliniken"></span>
                    <span class="logo-item"><img src="assets/logos/logo-3.png" alt="BLU Guxhagen"></span>
                    <span class="logo-item"><img src="assets/logos/logo-4.png" alt="SOLA Festival"></span>
                    <span class="logo-item"><img src="assets/logos/logo-5.png" alt="S-ART"></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PAINPOINT ===== -->
    <section class="section container" id="painpoint">
        <div class="painpoint-grid">
            <article class="painpoint-card is-left" data-reveal>
                <span class="painpoint-eyebrow">Szenario 1</span>
                <h3>Du machst noch kein Social Media.</h3>
                <p class="thought-hint">Tippe auf eine Denkblase, um den Gedanken zu öffnen.</p>
                <div class="thought-bubbles" data-thought-group>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">⏰</span>
                        <span class="thought-label">Keine Zeit</span>
                        <span class="thought-text">Keine Zeit neben dem Tagesgeschäft.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">❓</span>
                        <span class="thought-label">Unklarheit</span>
                        <span class="thought-text">Keine Ahnung, was wirklich funktioniert.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">🔄</span>
                        <span class="thought-label">Tempo</span>
                        <span class="thought-text">Plattformen ändern sich gefühlt im Wochentakt.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">🏃</span>
                        <span class="thought-label">Hinterher</span>
                        <span class="thought-text">Du kommst dem Tempo nicht hinterher.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">🤔</span>
                        <span class="thought-label">Was zeigen?</span>
                        <span class="thought-text">Unsicherheit, was du überhaupt zeigen sollst.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">📈</span>
                        <span class="thought-label">Konkurrenz</span>
                        <span class="thought-text">Die Konkurrenz wird täglich sichtbarer.</span>
                    </button>
                </div>
            </article>

            <article class="painpoint-card is-right" data-reveal>
                <span class="painpoint-eyebrow">Szenario 2</span>
                <h3>Du machst bereits Social Media.</h3>
                <p class="thought-hint">Tippe auf eine Denkblase, um den Gedanken zu öffnen.</p>
                <div class="thought-bubbles" data-thought-group>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">📉</span>
                        <span class="thought-label">Reichweite</span>
                        <span class="thought-text">Kaum Reichweite trotz regelmäßigem Posten.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">🎯</span>
                        <span class="thought-label">Zielgruppe</span>
                        <span class="thought-text">Falsche Zielgruppe, falsche Erwartungen.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">📭</span>
                        <span class="thought-label">Anfragen</span>
                        <span class="thought-text">Keine echten Kundenanfragen.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">🧭</span>
                        <span class="thought-label">Strategie</span>
                        <span class="thought-text">Keine Strategie, nur Bauchgefühl.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">❤️</span>
                        <span class="thought-label">Likes</span>
                        <span class="thought-text">Content ohne Wirkung, Likes ≠ Umsatz.</span>
                    </button>
                    <button type="button" class="thought-bubble" aria-expanded="false">
                        <span class="thought-icon" aria-hidden="true">⌛</span>
                        <span class="thought-label">Aufwand</span>
                        <span class="thought-text">Stunden investiert, ohne messbares Ergebnis.</span>
                    </button>
                </div>
            </article>
        </div>

        <div class="section-cta" data-reveal>
            <a class="btn btn-primary" href="#kontakt">
                Kostenloses Erstgespräch buchen
                <span class="arrow" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>
    </section>

    <!-- ===== HEBEL / LÖSUNG ===== -->
    <section class="section hebel-section" id="hebel">
        <div class="container">
            <div class="section-head" data-reveal>
                <div class="lead">
                    <h2 data-reveal-lines>
                        <span class="reveal-line"><span style="--reveal-index:0">Social Media ist</span></span>
                        <span class="reveal-line"><span style="--reveal-index:1"><span class="accent-text">der unsichtbare Hebel</span>.</span></span>
                    </h2>
                </div>
                <p data-reveal-soft style="--reveal-delay:220ms">
                    Wer ihn richtig ansetzt, hebt sich aus dem Rauschen heraus.
                    Wer ihn ignoriert, bleibt im Hintergrund, egal wie gut die Arbeit ist.
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
                        <g class="hebel-load-left" transform="translate(170, 200)">
                            <rect x="-72" y="-32" width="144" height="60" rx="12" fill="#101A30" stroke="rgba(255,255,255,0.10)" stroke-width="1"/>
                            <text x="0" y="-6" text-anchor="middle" class="hebel-side-label" fill="#F5F8FF">Unsichtbarkeit</text>
                            <text x="0" y="14" text-anchor="middle" class="hebel-side-sub" fill="rgba(245,248,255,0.55)">wenig Anfragen</text>
                        </g>

                        <!-- RIGHT LOAD: light, lifted "Reichweite + Vertrauen + Anfragen" -->
                        <g class="hebel-load-right" transform="translate(626, 198)">
                            <rect x="-92" y="-46" width="184" height="80" rx="14" fill="#13243A" stroke="rgba(108,211,193,0.5)" stroke-width="1.2"/>
                            <text x="0" y="-24" text-anchor="middle" class="hebel-side-label" fill="#6CD3C1">Reichweite</text>
                            <text x="0" y="-2"  text-anchor="middle" class="hebel-side-label" fill="#F5F8FF">Vertrauen</text>
                            <text x="0" y="20"  text-anchor="middle" class="hebel-side-label" fill="#F5F8FF">Kundenanfragen</text>
                        </g>

                        <!-- Cables -->
                        <line x1="170" y1="252" x2="170" y2="226" stroke="rgba(255,255,255,0.20)" stroke-width="1.2"/>
                        <line x1="626" y1="252" x2="626" y2="222" stroke="rgba(108,211,193,0.45)" stroke-width="1.4"/>
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
                <span class="hebel-line">Ein <strong class="hebel-emph">Koch</strong> sollte kochen.</span>
                <span class="hebel-line">Ein <strong class="hebel-emph">Arzt</strong> sollte behandeln.</span>
                <span class="hebel-line">Ein <strong class="hebel-emph">Handwerker</strong> sollte bauen.</span>
                <span class="hebel-line hebel-final">Wir übernehmen den Rest.</span>
            </div>

            <div class="section-cta" data-reveal>
                <a class="btn btn-primary" href="#kontakt">
                    Kostenloses Erstgespräch buchen
                    <span class="arrow" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                    </span>
                </a>
            </div>
        </div>
    </section>

    <!-- ===== LEISTUNGEN ===== -->
    <section class="section container" id="leistungen">
        <div class="service-accordion" data-reveal-stagger data-faq>
            <details class="service-acc" id="strategie">
                <summary class="service-acc-head">
                    <span class="service-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>
                    </span>
                    <span class="service-acc-titles">
                        <h3>Strategie</h3>
                        <p>Maßgeschneiderte Strategien für nachhaltiges Wachstum und maximale Reichweite.</p>
                    </span>
                    <span class="service-acc-arrow" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="service-acc-body">
                    <p>Wir bauen eine belastbare Grundlage mit Zielgruppen-Analyse, Positionierung und klarer Content-Roadmap.</p>
                    <ul>
                        <li>Mehr Klarheit in der Kommunikation</li>
                        <li>Höhere Trefferquote bei Kampagnen</li>
                        <li>Bessere Conversion über alle Kanäle</li>
                    </ul>
                </div>
            </details>

            <details class="service-acc" id="smm">
                <summary class="service-acc-head">
                    <span class="service-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/></svg>
                    </span>
                    <span class="service-acc-titles">
                        <h3>Social Media Management</h3>
                        <p>Wir übernehmen Plattform, Community &amp; Content, damit du dich um nichts kümmern musst.</p>
                    </span>
                    <span class="service-acc-arrow" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="service-acc-body">
                    <p>Wir steuern deinen Auftritt täglich und sorgen für konsistente Veröffentlichung und Community-Nähe.</p>
                    <ul>
                        <li>Regelmäßiger, hochwertiger Output</li>
                        <li>Messbare Kontinuität</li>
                        <li>Mehr Vertrauen bei deiner Zielgruppe</li>
                    </ul>
                </div>
            </details>

            <details class="service-acc" id="performance">
                <summary class="service-acc-head">
                    <span class="service-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22 22 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
                    </span>
                    <span class="service-acc-titles">
                        <h3>Performance Marketing</h3>
                        <p>Gezielte Kampagnen, die nicht nur Reichweite bringen, sondern Ergebnisse liefern.</p>
                    </span>
                    <span class="service-acc-arrow" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="service-acc-body">
                    <p>Von Setup bis Skalierung: Kampagnen werden laufend getestet und auf Profitabilität optimiert.</p>
                    <ul>
                        <li>Mehr qualifizierte Leads</li>
                        <li>Effizienterer Ad-Spend</li>
                        <li>Steigender Umsatz</li>
                    </ul>
                </div>
            </details>
        </div>

        <!-- Stats — temporär ausgeblendet (nur unsichtbar, Markup bleibt erhalten).
             Zum Wiedereinblenden das style="display:none" entfernen. -->
        <div class="stats" aria-label="Unsere Zahlen" data-reveal-stagger style="display:none">
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <div>
                    <div class="stat-num" data-count-up="2.5" data-count-decimals="1" data-count-suffix=" Mio.+">2,5 Mio.+</div>
                    <div class="stat-label">erreichte<br>Konten</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </span>
                <div>
                    <div class="stat-num" data-count-up="50" data-count-suffix="K+">50K+</div>
                    <div class="stat-label">generierte<br>Follower</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                </span>
                <div>
                    <div class="stat-num" data-count-up="200" data-count-suffix="+">200+</div>
                    <div class="stat-label">gewonnene<br>Leads</div>
                </div>
            </div>
        </div>

        <div class="section-cta" data-reveal>
            <a class="btn btn-primary" href="#kontakt">
                Kostenloses Erstgespräch buchen
                <span class="arrow" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>
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

    <!-- ===== ERGEBNISSE / CASE STUDIES =====
         Temporär ausgeblendet (aus dem Rendering-Baum entfernt).
         Zum Reaktivieren das umschließende PHP-if(false) entfernen. -->
    <?php if (false): ?>
    <section class="section container" id="case-studies">
        <span id="projekte" aria-hidden="true" style="position:absolute"></span>
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
    <?php endif; ?>

    <!-- ===== ÜBER UNS ===== -->
    <section class="section container" id="ueber-uns">
        <!-- Stabiles Sprungziel für /projekte-Weiterleitungen (projekte.php / .html),
             solange die Ergebnisse/Case-Studies-Sektion ausgeblendet ist. -->
        <span id="projekte" aria-hidden="true" style="position:absolute"></span>
        <div class="section-head" data-reveal>
            <div class="lead">
                <h2 data-reveal-lines>
                    <span class="reveal-line"><span style="--reveal-index:0">Hinter den Projekten</span></span>
                    <span class="reveal-line"><span style="--reveal-index:1"><span class="accent-text">stehen Menschen</span>.</span></span>
                </h2>
            </div>
            <div class="about-intro" data-reveal-soft style="--reveal-delay:200ms">
                <p>
                    Ein starker Social-Media-Auftritt und erfolgreiche digitale Projekte entstehen nicht
                    durch Zufall. Sie sind das Ergebnis von strategischer Planung, kreativen Ideen und einer
                    absolut zuverlässigen Umsetzung. Wir von Dodidis Media &amp; Event verstehen uns nicht als
                    externe Agentur, die einfach nur Aufgaben abarbeitet. Wir sehen uns als fester Partner an
                    eurer Seite. Unser Ziel ist es, eure Botschaft authentisch, modern und zielgerichtet digital
                    sichtbar zu machen – damit ihr genau die Zielgruppe erreicht, die zu eurem Unternehmen passt.
                </p>
                <p>
                    Hinter den Projekten stehen Menschen, die digitale Medien von Grund auf verstehen und mit
                    Leidenschaft füllen. Wir bringen unsere jeweilige Expertise ein, um euer Projekt ganzheitlich
                    und persönlich zu betreuen.
                </p>
            </div>
        </div>

        <div class="team-bubbles" data-reveal-stagger>
            <article class="team-bubble">
                <div class="team-avatar">
                    <img src="assets/team/timo-block-sq.jpg" alt="Timo Block" width="220" height="220" loading="lazy" decoding="async"
                         onerror="this.style.display='none';this.parentNode.classList.add('is-fallback');">
                    <span class="team-avatar-fallback" aria-hidden="true">TB</span>
                </div>
                <h3 class="team-name">Timo Block</h3>
                <p class="team-role">
                    Verantwortlich für die strategische Ausrichtung und euer persönlicher
                    Ansprechpartner für nachhaltiges Wachstum.
                </p>
            </article>

            <article class="team-bubble">
                <div class="team-avatar">
                    <img src="assets/team/raphael-dodidis-sq.jpg" alt="Raphael Dodidis" width="220" height="220" loading="lazy" decoding="async"
                         onerror="this.style.display='none';this.parentNode.classList.add('is-fallback');">
                    <span class="team-avatar-fallback" aria-hidden="true">RD</span>
                </div>
                <h3 class="team-name">Raphael Dodidis</h3>
                <p class="team-role">
                    Verwandelt Ideen in visuell überzeugenden Content. Sorgt dafür, dass eure
                    Marke modern und professionell wahrgenommen wird.
                </p>
            </article>
        </div>

        <div class="section-cta" data-reveal>
            <a class="btn btn-primary" href="#kontakt">
                Lernen wir uns kennen
                <span class="arrow" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>
    </section>

    <!-- ===== KONTAKT ===== -->
    <section class="section container" id="kontakt">
        <div class="contact-grid" data-reveal>
            <div class="contact-card">
                <h3>Warum ein kostenloses Erstgespräch?</h3>
                <p>30 Minuten, in denen wir Potenziale, Prioritäten und Schritte sortieren.</p>
                <ul>
                    <li>Klare Strategie-Empfehlung</li>
                    <li>Realistische Zeitplanung</li>
                    <li>Direkter Maßnahmenplan</li>
                </ul>

                <ul class="contact-meta-list">
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <a href="mailto:kontakt@dodidis-media.de">kontakt@dodidis-media.de</a>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <a href="tel:+4915229242977">+49 152 29242977</a>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>Im Herzen von Nordhessen</span>
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
                        Kostenloses Erstgespräch anfragen
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

<script src="assets/site.js?v=<?= @filemtime(__DIR__ . '/assets/site.js') ?>"></script>
</body>
</html>
