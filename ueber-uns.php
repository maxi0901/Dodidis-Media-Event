<?php $activePage = 'ueber-uns'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="description" content="Dodidis.Media – Junge Social Media Marketing Agentur aus Nordhessen mit Fokus auf messbare Resultate.">
    <title>Über uns | Dodidis.Media</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main>
    <section class="page-hero container reveal">
        <span class="eyebrow">Über uns</span>
        <h1>Wir produzieren keine Videos.<br><span class="accent-text">Wir liefern Ergebnisse.</span></h1>
        <p class="lead-sub">
            Eine junge Social-Media-Agentur aus Nordhessen mit klarem Fokus auf
            Resultate statt Show. Kurze Wege, schnelle Entscheidungen, messbare Wirkung.
        </p>
    </section>

    <section class="section container reveal">
        <div class="about-grid">
            <article class="about-card">
                <h3>Wer wir sind</h3>
                <p>
                    Wir sind ein kleines Kernteam aus Strateg:innen, Creator:innen und Marketern,
                    die Social Media als Werkzeug verstehen – nicht als Bühne.
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

        <div class="stats" aria-label="Unsere Zahlen">
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

        <div class="cta-band reveal">
            <div>
                <h2>Lust auf eine echte Zusammenarbeit?</h2>
                <p>30 Minuten reichen, um die nächsten konkreten Schritte zu klären.</p>
            </div>
            <a class="btn btn-primary" href="kontakt.php">
                Erstgespräch buchen
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
