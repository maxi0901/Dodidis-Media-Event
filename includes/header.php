<?php
if (!isset($activePage)) {
    $activePage = '';
}
?>
<header class="site-header site-header-sticky">
  <div class="container nav-wrap">
    <a class="brand" href="index.php">DODIDIS.MEDIA</a>
    <nav class="top-nav" aria-label="Hauptnavigation">
      <a class="<?= $activePage === 'leistungen' ? 'active' : '' ?>" href="leistungen.php">Leistungen</a>
      <a class="<?= $activePage === 'projekte' ? 'active' : '' ?>" href="projekte.php">Projekte</a>
      <a class="<?= $activePage === 'ueber-uns' ? 'active' : '' ?>" href="ueber-uns.php">Über uns</a>
      <a class="<?= $activePage === 'kontakt' ? 'active' : '' ?>" href="kontakt.php">Kontakt</a>
    </nav>
  </div>
</header>
