<?php
if (!isset($navBase)) {
    $navBase = (isset($activePage) && $activePage === 'home') ? '' : 'index.php';
}
?>
<footer class="site-footer" id="kontakt-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="<?= $navBase === '' ? '#home' : 'index.php' ?>">
                    <span class="brand-mark" aria-hidden="true">DM</span>
                    <span class="brand-name">DODIDIS.MEDIA</span>
                </a>
                <p>
                    Wir sind die junge Social Media Marketing Agentur aus Nordhessen
                    und helfen Brands, online sichtbar zu werden und zu wachsen.
                </p>
                <div class="socials" aria-label="Social Media">
                    <a href="https://www.instagram.com/dodidis.media" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="https://www.tiktok.com/@dodidis.media" target="_blank" rel="noopener" aria-label="TikTok">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5.8 20.1a6.34 6.34 0 0 0 10.86-4.43V8.36a8.16 8.16 0 0 0 4.77 1.52V6.43a4.85 4.85 0 0 1-1.84-.31z"/></svg>
                    </a>
                    <a href="https://www.youtube.com/@dodidis.media" target="_blank" rel="noopener" aria-label="YouTube">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                    </a>
                    <a href="https://www.linkedin.com/company/dodidis-media" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="<?= $navBase === '' ? '#home' : 'index.php' ?>">Home</a></li>
                    <li><a href="<?= $navBase ?>#leistungen">Leistungen</a></li>
                    <li><a href="<?= $navBase ?>#projekte">Projekte</a></li>
                    <li><a href="<?= $navBase ?>#ueber-uns">Über uns</a></li>
                    <li><a href="<?= $navBase ?>#kontakt">Kontakt</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Leistungen</h4>
                <ul>
                    <li><a href="<?= $navBase ?>#strategie">Strategie</a></li>
                    <li><a href="<?= $navBase ?>#content">Content Creation</a></li>
                    <li><a href="<?= $navBase ?>#smm">Social Media Management</a></li>
                    <li><a href="<?= $navBase ?>#performance">Performance Marketing</a></li>
                </ul>
            </div>

            <div class="footer-col contact">
                <h4>Kontakt</h4>
                <ul class="contact-list">
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
        </div>

        <div class="sub-footer">
            <span>© <?= date('Y') ?> Dodidis.Media – Alle Rechte vorbehalten</span>
            <div class="legal">
                <a href="impressum.php">Impressum</a>
                <a href="datenschutz.php">Datenschutz</a>
            </div>
        </div>
    </div>
</footer>
