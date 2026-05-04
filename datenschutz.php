<?php $activePage = 'datenschutz'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="description" content="Datenschutzerklärung von Dodidis.Media.">
    <title>Datenschutz | Dodidis.Media</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main>
    <section class="page-hero container reveal">
        <span class="eyebrow">Rechtliches</span>
        <h1>Datenschutz</h1>
    </section>

    <section class="section container reveal">
        <article class="prose-card">
            <h2>1. Verantwortlicher</h2>
            <p>
                Dodidis.Media, Nordhessen, Deutschland.<br>
                E-Mail: <a href="mailto:hallo@dodidis-media.de">hallo@dodidis-media.de</a>
            </p>

            <h2>2. Erhebung &amp; Verarbeitung personenbezogener Daten</h2>
            <p>
                Beim Besuch unserer Website werden technisch notwendige Daten (z.B. IP-Adresse,
                Browser, Zeitpunkt) verarbeitet. Bei Nutzung des Kontaktformulars verarbeiten
                wir die von dir angegebenen Daten ausschließlich zur Bearbeitung deiner Anfrage.
            </p>

            <h2>3. Rechtsgrundlage</h2>
            <p>
                Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO (Vertragsanbahnung)
                bzw. Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an einem sicheren Webbetrieb).
            </p>

            <h2>4. Speicherdauer</h2>
            <p>
                Wir speichern personenbezogene Daten nur so lange, wie es für die jeweiligen Zwecke
                erforderlich ist oder gesetzliche Aufbewahrungsfristen es erfordern.
            </p>

            <h2>5. Deine Rechte</h2>
            <ul>
                <li>Auskunft über deine gespeicherten Daten</li>
                <li>Berichtigung unrichtiger Daten</li>
                <li>Löschung („Recht auf Vergessenwerden")</li>
                <li>Einschränkung der Verarbeitung</li>
                <li>Widerspruch gegen die Verarbeitung</li>
                <li>Datenübertragbarkeit</li>
                <li>Beschwerde bei der zuständigen Aufsichtsbehörde</li>
            </ul>

            <h2>6. Externe Inhalte</h2>
            <p>
                Wir setzen Webfonts von Google ein. Beim Aufruf werden technische Daten an Google
                übertragen. Details findest du in der Datenschutzerklärung von Google.
            </p>

            <p style="margin-top: 2rem;">
                <a href="index.php">← Zurück zur Startseite</a>
            </p>
        </article>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="assets/site.js"></script>
</body>
</html>
