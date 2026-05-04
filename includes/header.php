<?php
if (!isset($activePage)) {
    $activePage = '';
}
?>
<div class="ambient" aria-hidden="true">
    <span class="orb orb-1"></span>
    <span class="orb orb-2"></span>
    <span class="orb orb-3"></span>
</div>

<div class="nav-wrap">
    <nav class="nav" aria-label="Hauptnavigation">
        <a class="brand" href="index.php" aria-label="Dodidis Media Startseite">
            <span class="brand-mark" aria-hidden="true">DM</span>
            <span class="brand-name">DODIDIS.MEDIA</span>
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="index.php" class="<?= $activePage === 'home' ? 'is-active' : '' ?>"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?>>Home</a></li>
            <li><a href="leistungen.php" class="<?= $activePage === 'leistungen' ? 'is-active' : '' ?>"<?= $activePage === 'leistungen' ? ' aria-current="page"' : '' ?>>Leistungen</a></li>
            <li><a href="projekte.php" class="<?= $activePage === 'projekte' ? 'is-active' : '' ?>"<?= $activePage === 'projekte' ? ' aria-current="page"' : '' ?>>Projekte</a></li>
            <li><a href="ueber-uns.php" class="<?= $activePage === 'ueber-uns' ? 'is-active' : '' ?>"<?= $activePage === 'ueber-uns' ? ' aria-current="page"' : '' ?>>Über uns</a></li>
            <li><a href="kontakt.php" class="<?= $activePage === 'kontakt' ? 'is-active' : '' ?>"<?= $activePage === 'kontakt' ? ' aria-current="page"' : '' ?>>Kontakt</a></li>
        </ul>

        <a class="btn btn-primary nav-cta" href="kontakt.php">
            Erstgespräch buchen
            <span class="arrow" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Menü öffnen" aria-controls="navLinks" aria-expanded="false" id="navToggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </nav>
</div>
