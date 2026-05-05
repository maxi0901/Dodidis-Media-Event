<?php
if (!isset($activePage)) {
    $activePage = '';
}
$navBase = ($activePage === 'home') ? '' : 'index.php';
?>
<div class="ambient" aria-hidden="true">
    <span class="orb orb-1"></span>
    <span class="orb orb-2"></span>
    <span class="orb orb-3"></span>
</div>

<div class="nav-wrap">
    <nav class="nav" aria-label="Hauptnavigation">
        <a class="brand" href="<?= $navBase === '' ? '#home' : 'index.php' ?>" aria-label="Dodidis Media Startseite">
            <span class="brand-mark" aria-hidden="true">DM</span>
            <span class="brand-name">DODIDIS.MEDIA</span>
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= $navBase ?>#leistungen">Leistungen</a></li>
            <li><a href="<?= $navBase ?>#projekte">Projekte</a></li>
            <li><a href="<?= $navBase ?>#ueber-uns">Über uns</a></li>
            <li><a href="<?= $navBase ?>#kontakt">Kontakt</a></li>
        </ul>

        <a class="btn btn-primary nav-cta" href="<?= $navBase ?>#kontakt">
            Erstgespräch buchen
            <span class="arrow" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Menü öffnen" aria-controls="navLinks" aria-expanded="false" id="navToggle">
            <span class="nav-toggle-icon" aria-hidden="true"></span>
        </button>
    </nav>
</div>
