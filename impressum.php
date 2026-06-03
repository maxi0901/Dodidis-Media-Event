<?php $activePage = 'impressum'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="description" content="Impressum von Dodidis.Media.">
    <title>Impressum | Dodidis.Media</title>
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
        <h1>Impressum</h1>
    </section>

    <section class="section container reveal">
        <article class="prose-card">
            <h2>Angaben gemäß § 5 TMG</h2>
            <p>
                Dodidis.Media<br>
                Inhaber: Dodidis Media<br>
                Nordhessen, Deutschland
            </p>

            <h2>Kontakt</h2>
            <p>
                E-Mail: <a href="mailto:kontakt@dodidis-media.de">kontakt@dodidis-media.de</a><br>
                Telefon: <a href="tel:+4917660172907">+49 176 60172907</a>
            </p>

            <h2>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
            <p>
                Dodidis Media<br>
                Nordhessen, Deutschland
            </p>

            <h2>Haftungsausschluss</h2>
            <p>
                Die Inhalte unserer Seiten wurden mit größter Sorgfalt erstellt. Für die
                Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch
                keine Gewähr übernehmen.
            </p>

            <h2>Urheberrecht</h2>
            <p>
                Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten
                unterliegen dem deutschen Urheberrecht. Beiträge Dritter sind als solche
                gekennzeichnet.
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
