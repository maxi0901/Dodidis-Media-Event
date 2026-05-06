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
    <section class="hero container" id="hero">
        <div class="hero-grid">
            <div class="hero-text" data-reveal-stagger>
                <span class="eyebrow">Sichtbarkeit · Wachstum · Vertrauen</span>
                <h1 class="hero-headline-xl">
                    Wir machen keine Videos.<br>
                    <span class="accent-text">Wir sorgen dafür, dass deine Marke wahrgenommen wird.</span>
                </h1>
                <p class="hero-sub">
                    Social Media ist heute kein Extra mehr — sondern der unsichtbare Hebel
                    zwischen Wachstum und Bedeutungslosigkeit.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#kontakt">
                        Erstgespräch buchen
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                    <a class="btn btn-ghost" href="#kontakt">
                        Kostenlos analysieren lassen
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                </div>
                <div class="hero-trust-row" aria-label="Vertrauensindikatoren">
                    <span class="hero-trust-item"><strong>30+</strong> Projekte</span>
                    <span class="hero-trust-item"><strong>2,5 Mio+</strong> Reichweite</span>
                    <span class="hero-trust-item"><strong>98 %</strong> Kundenzufriedenheit</span>
                </div>
            </div>

            <div class="hero-visual" data-reveal aria-hidden="true">
                <!--
                    HERO FOUNDER VISUAL
                    Slot für späteres echtes Foto:
                    <img src="assets/hero-founders.jpg" alt="Zwei Gründer Rücken an Rücken" class="hero-photo" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:28px;z-index:2;">
                -->
                <div class="hero-founders">
                    <div class="hero-founders-glow"></div>
                    <svg class="hero-founders-svg" viewBox="0 0 500 400" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                        <defs>
                            <linearGradient id="founderTeal" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#6CD3C1" stop-opacity="0.85"/>
                                <stop offset="100%" stop-color="#358F81" stop-opacity="0.55"/>
                            </linearGradient>
                            <linearGradient id="founderRim" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#46A99A" stop-opacity="0.0"/>
                                <stop offset="100%" stop-color="#6CD3C1" stop-opacity="0.95"/>
                            </linearGradient>
                            <radialGradient id="floorLight" cx="50%" cy="100%" r="60%">
                                <stop offset="0%" stop-color="#6CD3C1" stop-opacity="0.30"/>
                                <stop offset="100%" stop-color="#0E1422" stop-opacity="0"/>
                            </radialGradient>
                        </defs>

                        <!-- Cinematic floor light -->
                        <ellipse cx="250" cy="400" rx="240" ry="60" fill="url(#floorLight)"/>

                        <!-- LEFT FOUNDER (Camera) -->
                        <g transform="translate(150, 110)">
                            <!-- Body -->
                            <path d="M-22 90 Q-32 120 -38 200 L-12 200 L-8 120 Q-2 100 0 92 Z" fill="#0E1A2C" stroke="url(#founderRim)" stroke-width="1.2"/>
                            <!-- Shoulder/arm holding camera -->
                            <path d="M-2 92 Q14 86 28 102 L40 130 L20 138 L8 110 Z" fill="#0F1E32" stroke="url(#founderRim)" stroke-width="1"/>
                            <!-- Head -->
                            <ellipse cx="-8" cy="68" rx="20" ry="24" fill="#101F36" stroke="url(#founderRim)" stroke-width="1.2"/>
                            <!-- Hair -->
                            <path d="M-26 60 Q-24 44 -8 42 Q10 44 12 60 Q12 52 4 50 Q-2 56 -14 56 Q-24 56 -26 60 Z" fill="#0A1424"/>
                            <!-- Camera body -->
                            <rect x="32" y="116" width="42" height="26" rx="4" fill="#1C2940" stroke="url(#founderRim)" stroke-width="1.4"/>
                            <!-- Lens -->
                            <circle cx="78" cy="129" r="14" fill="#0E1A2C" stroke="#6CD3C1" stroke-width="1.6"/>
                            <circle cx="78" cy="129" r="8" fill="#0A1220" stroke="#6CD3C1" stroke-width="1" opacity="0.8"/>
                            <circle cx="78" cy="129" r="3" fill="#6CD3C1" opacity="0.85"/>
                            <!-- Viewfinder bump -->
                            <rect x="44" y="110" width="12" height="8" fill="#1C2940" stroke="url(#founderRim)" stroke-width="0.8"/>
                            <!-- Recording dot -->
                            <circle cx="36" cy="120" r="2" fill="#FF5A5A" opacity="0.9"/>
                        </g>

                        <!-- RIGHT FOUNDER (MacBook) -->
                        <g transform="translate(350, 110)">
                            <!-- Body -->
                            <path d="M22 90 Q32 120 38 200 L12 200 L8 120 Q2 100 0 92 Z" fill="#0E1A2C" stroke="url(#founderRim)" stroke-width="1.2" transform="scale(-1,1)"/>
                            <!-- Arm forward holding laptop -->
                            <path d="M-2 92 Q-22 90 -38 108 L-50 138 L-28 144 L-12 116 Z" fill="#0F1E32" stroke="url(#founderRim)" stroke-width="1"/>
                            <!-- Head -->
                            <ellipse cx="8" cy="68" rx="20" ry="24" fill="#101F36" stroke="url(#founderRim)" stroke-width="1.2"/>
                            <!-- Hair -->
                            <path d="M26 60 Q24 44 8 42 Q-10 44 -12 60 Q-12 52 -4 50 Q2 56 14 56 Q24 56 26 60 Z" fill="#0A1424"/>
                            <!-- MacBook (closed-to-open laptop) -->
                            <g transform="translate(-66, 108) rotate(-8)">
                                <!-- Base -->
                                <path d="M0 36 L66 36 L70 42 L-4 42 Z" fill="#1A2640" stroke="url(#founderRim)" stroke-width="1"/>
                                <!-- Screen back -->
                                <rect x="2" y="0" width="62" height="38" rx="3" fill="#101A30" stroke="url(#founderRim)" stroke-width="1.4"/>
                                <!-- Screen content -->
                                <rect x="6" y="4" width="54" height="30" rx="2" fill="#0A1220"/>
                                <!-- Bars (analytics) -->
                                <rect x="10"  y="22" width="4" height="8" fill="#46A99A" opacity="0.85"/>
                                <rect x="16"  y="18" width="4" height="12" fill="#6CD3C1" opacity="0.85"/>
                                <rect x="22"  y="14" width="4" height="16" fill="#46A99A" opacity="0.85"/>
                                <rect x="28"  y="10" width="4" height="20" fill="#6CD3C1" opacity="0.95"/>
                                <rect x="34"  y="8"  width="4" height="22" fill="#6CD3C1"/>
                                <rect x="40"  y="12" width="4" height="18" fill="#46A99A" opacity="0.85"/>
                                <rect x="46"  y="16" width="4" height="14" fill="#358F81" opacity="0.85"/>
                                <rect x="52"  y="20" width="4" height="10" fill="#358F81" opacity="0.7"/>
                                <!-- Apple-ish dot -->
                                <circle cx="33" cy="38" r="0.8" fill="#46A99A"/>
                            </g>
                        </g>

                        <!-- Faint cinematic mist -->
                        <rect x="0" y="0" width="500" height="400" fill="url(#founderTeal)" opacity="0.06"/>
                    </svg>
                    <span class="hero-founders-tag">Behind the Brand</span>
                </div>
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
                <h2 class="painpoint-headline">
                    Das Problem ist nicht dein Produkt.<br>
                    <span class="accent-text">Das Problem ist, dass dich niemand wahrnimmt.</span>
                </h2>
            </div>
            <p>
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
                    <svg class="painpoint-chart" viewBox="0 0 320 100" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <!-- Sinkende Kurve -->
                        <path d="M0 30 L40 25 L80 40 L120 35 L160 55 L200 60 L240 70 L280 78 L320 85" stroke="#46A99A" opacity="0.55"/>
                        <!-- Datenpunkte -->
                        <circle cx="40"  cy="25" r="3" fill="#6CD3C1"/>
                        <circle cx="120" cy="35" r="3" fill="#46A99A"/>
                        <circle cx="200" cy="60" r="3" fill="#358F81" opacity="0.7"/>
                        <circle cx="280" cy="78" r="3" fill="#358F81" opacity="0.5"/>
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
                    <h2>Social Media ist <span class="accent-text">der unsichtbare Hebel</span>.</h2>
                </div>
                <p>
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

    <!-- ===== AGENCY PHONE SHOWCASE ===== -->
    <section class="section agency-phone-section phone-scroll-story" id="showcase" data-phone-scroll-story>
        <div class="container">
            <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">So machen wir dich sichtbar</span>
                <h2>Content, der nicht <span class="accent-text">scrollend ignoriert</span> wird.</h2>
            </div>
            <p>
                Vom ersten Frame, der den Daumen stoppt, bis zur Anfrage in deinem Posteingang –
                ein Blick in die Praxis und in das, was wir wirklich liefern.
            </p>
            </div>

            <div class="phone-story-pin">
                <div class="phone-story-layout">
            <div class="agency-phone-stage" data-phone-stage aria-hidden="true">
                <div class="agency-phone-glow"></div>
                <div class="agency-phone-mockup" data-phone-mockup>
                    <div class="agency-phone-frame">
                        <span class="agency-phone-side"></span>
                        <span class="agency-phone-side agency-phone-side-2"></span>
                        <span class="agency-phone-notch"></span>
                        <div class="agency-phone-screen">
                            <div class="agency-phone-content" data-phone-content>
                                <div class="agency-phone-status">
                                    <span>9:41</span>
                                    <span class="status-icons">
                                        <span class="status-dot"></span>
                                        <span class="status-dot"></span>
                                        <span class="status-dot"></span>
                                    </span>
                                </div>

                                <div class="agency-phone-header">
                                    <span class="ph-brand">
                                        <span class="ph-brand-mark">DM</span>
                                        dodidis.media
                                    </span>
                                    <span class="ph-actions">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                                    </span>
                                </div>

                                <div class="agency-phone-stories">
                                    <div class="agency-phone-story">
                                        <div class="story-ring"><div class="story-inner">DM</div></div>
                                        <span>live</span>
                                    </div>
                                    <div class="agency-phone-story">
                                        <div class="story-ring"><div class="story-inner">RE</div></div>
                                        <span>reels</span>
                                    </div>
                                    <div class="agency-phone-story">
                                        <div class="story-ring"><div class="story-inner">EV</div></div>
                                        <span>event</span>
                                    </div>
                                    <div class="agency-phone-story">
                                        <div class="story-ring"><div class="story-inner">BR</div></div>
                                        <span>brand</span>
                                    </div>
                                </div>

                                <div class="agency-phone-post">
                                    <div class="post-head">
                                        <span class="post-avatar"></span>
                                        <span>dodidis.media</span>
                                        <span class="post-meta">vor 2 Std.</span>
                                    </div>
                                    <div class="post-media">
                                        <span class="play"></span>
                                    </div>
                                    <div class="post-stats">
                                        <span>12.4K Views</span>
                                        <span>1.8K Likes</span>
                                    </div>
                                </div>

                                <div class="agency-phone-metric-row">
                                    <div class="agency-phone-metric">
                                        <div class="metric-num">+168%</div>
                                        <div class="metric-label">Reichweite</div>
                                    </div>
                                    <div class="agency-phone-metric">
                                        <div class="metric-num">+42%</div>
                                        <div class="metric-label">Leads</div>
                                    </div>
                                    <div class="agency-phone-metric">
                                        <div class="metric-num">98%</div>
                                        <div class="metric-label">Retention</div>
                                    </div>
                                </div>

                                <div class="agency-phone-card-cta">
                                    <span>Erstgespräch buchen</span>
                                    <span class="cta-arrow">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                                    </span>
                                </div>

                                <div class="agency-phone-post">
                                    <div class="post-head">
                                        <span class="post-avatar"></span>
                                        <span>event.series</span>
                                        <span class="post-meta">heute</span>
                                    </div>
                                    <div class="post-media">
                                        <span class="play"></span>
                                    </div>
                                    <div class="post-stats">
                                        <span>8.9K Views</span>
                                        <span>720 Saves</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                    <div class="phone-extracted-cards" aria-label="Social Media Leistungen">
                <article class="glass-card hover-lift phone-extract-card" data-step="1">
                    <div class="feat-head">
                        <div class="feat-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                        <h3>Kurzformate, die hängen bleiben</h3>
                    </div>
                    <p>Hooks, Pacing und Story – so dass dein Auftritt nicht weggewischt, sondern weiterempfohlen wird.</p>
                </article>

                <article class="glass-card hover-lift phone-extract-card" data-step="2">
                    <div class="feat-head">
                        <div class="feat-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>
                        </div>
                        <h3>Strategie statt Bauchgefühl</h3>
                    </div>
                    <p>Jeder Post hat ein Ziel: Reichweite, Vertrauen oder Anfrage. Wir messen, lernen und skalieren das, was wirkt.</p>
                </article>

                <article class="glass-card hover-lift phone-extract-card" data-step="3">
                    <div class="feat-head">
                        <div class="feat-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </div>
                        <h3>Verlässlichkeit, die du spürst</h3>
                    </div>
                    <p>Konstanter Output ohne Reibungsverluste – damit dein Auftritt wächst, während du dich auf dein Kerngeschäft konzentrierst.</p>
                </article>

                <article class="glass-card hover-lift phone-extract-card" data-step="4">
                    <div class="feat-head">
                        <div class="feat-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.77 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        </div>
                        <h3>Premium-Look ohne Stockfoto-Vibes</h3>
                    </div>
                    <p>Visuelle Wiedererkennbarkeit, die Vertrauen schafft – und deine Marke in den Vordergrund stellt.</p>
                </article>
                    </div>
                </div>
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

    <!-- ===== ERGEBNISSE / CASE STUDIES ===== -->
    <section class="section container" id="case-studies">
        <span id="projekte" aria-hidden="true" style="position:absolute"></span>
        <div class="section-head" data-reveal>
            <div class="lead">
                <span class="eyebrow">Ergebnisse</span>
                <h2>Sichtbarkeit, die in <span class="accent-text">Anfragen</span> mündet.</h2>
            </div>
            <p>
                Ausgewählte Produktionen, Reels und Performance-Kampagnen –
                für Marken, die mehr als nur schöne Bilder wollten.
            </p>
        </div>

        <div class="projects-grid" data-reveal-stagger>
            <a class="project project-1" href="#projekte">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Social Media &amp; Reels</span>
            </a>
            <a class="project project-2" href="#projekte">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Event Content</span>
            </a>
            <a class="project project-3" href="#projekte">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Video Production</span>
            </a>
            <a class="project project-4" href="#projekte">
                <div class="img" aria-hidden="true"></div>
                <span class="vendor">VB</span>
                <span class="label">Social Media &amp; Ads</span>
            </a>
            <a class="project project-5" href="lp/gutshof-maifest-2026/">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Maifest 2026</span>
            </a>
            <a class="project project-6" href="#projekte">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Brand Launch</span>
            </a>
            <a class="project project-7" href="#projekte">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Restaurant Series</span>
            </a>
            <a class="project project-8" href="#projekte">
                <div class="img" aria-hidden="true"></div>
                <span class="label">E-Commerce Scale</span>
            </a>
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
            <article class="about-card">
                <h3>Kooperation mit blockfilms</h3>
                <p>
                    Für größere Produktionen arbeiten wir in Kooperation mit
                    <strong>blockfilms</strong> – als kreativer Produktionspartner, nicht als Kunde.
                </p>
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
