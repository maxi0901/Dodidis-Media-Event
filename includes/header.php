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
            <li class="nav-menu-kicker" aria-hidden="true">Menü</li>
            <li><a href="<?= $navBase ?>#leistungen">Leistungen</a></li>
            <li><a href="<?= $navBase ?>#ueber-uns">Über uns</a></li>
            <li><a href="<?= $navBase ?>#kontakt">Kontakt</a></li>
            <li class="nav-mobile-portal">
                <a href="/agenturtool/login.php">Kundenportal</a>
            </li>
            <li class="nav-mobile-cta">
                <a href="<?= $navBase ?>#kontakt">Kostenloses Erstgespräch buchen</a>
            </li>
        </ul>

        <a class="nav-portal" href="/agenturtool/login.php" aria-label="Zum Kundenportal">
            <span class="nav-portal-icon" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <span class="nav-portal-label">Kundenportal</span>
        </a>

        <a class="btn btn-primary nav-cta" href="<?= $navBase ?>#kontakt">
            Kostenloses Erstgespräch buchen
            <span class="arrow" aria-hidden="true">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
            </span>
        </a>

        <button class="nav-toggle" type="button" aria-label="Menü öffnen" aria-controls="navLinks" aria-expanded="false" id="navToggle">
            <span class="nav-toggle-icon" aria-hidden="true"></span>
        </button>
    </nav>
</div>
