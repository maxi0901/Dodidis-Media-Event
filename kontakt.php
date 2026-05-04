<?php $activePage = 'kontakt'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="description" content="Kontakt zu Dodidis.Media – Lass uns über dein Projekt sprechen.">
    <title>Kontakt | Dodidis.Media</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main>
    <section class="page-hero container reveal">
        <span class="eyebrow">Kontakt</span>
        <h1>Lass uns über dein <span class="accent-text">Projekt</span> sprechen.</h1>
        <p class="lead-sub">
            In 30 Minuten klären wir Potenziale, Prioritäten und die nächsten konkreten Schritte –
            unverbindlich und ehrlich.
        </p>
    </section>

    <section class="section container reveal">
        <div class="contact-grid">
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
