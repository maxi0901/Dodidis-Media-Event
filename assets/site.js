(function () {
    var prefersReduced = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ============================================================
       Scroll reveal — IntersectionObserver, no library
       ============================================================ */
    var revealTargets = document.querySelectorAll(
        '[data-reveal], [data-reveal-stagger], .reveal'
    );

    // Stagger: assign --reveal-index to each direct child
    document.querySelectorAll('[data-reveal-stagger]').forEach(function (group) {
        var children = group.children;
        for (var i = 0; i < children.length; i++) {
            children[i].style.setProperty('--reveal-index', i);
        }
    });

    if (prefersReduced || !('IntersectionObserver' in window)) {
        revealTargets.forEach(function (el) { el.classList.add('is-visible'); });
    } else {
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.14,
            rootMargin: '0px 0px -8% 0px'
        });
        revealTargets.forEach(function (el) { io.observe(el); });
    }

    /* ============================================================
       Mobile navigation — robust open/close
       ============================================================ */
    var toggle = document.getElementById('navToggle');
    var links  = document.getElementById('navLinks');
    var navWrap = document.querySelector('.nav-wrap');

    if (toggle && links) {
        var isOpen = function () {
            return toggle.getAttribute('aria-expanded') === 'true';
        };

        var setOpen = function (open) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
            links.classList.toggle('open', open);
            document.body.classList.toggle('is-menu-open', open);
        };

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(!isOpen());
        });

        // Close when tapping a navigation link
        links.addEventListener('click', function (e) {
            var link = e.target.closest('a');
            if (link) setOpen(false);
        });

        // Close when clicking outside the navbar
        document.addEventListener('click', function (e) {
            if (!isOpen()) return;
            if (navWrap && !navWrap.contains(e.target)) setOpen(false);
        });

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && isOpen()) {
                setOpen(false);
                toggle.focus();
            }
        });

        // Reset state when leaving mobile breakpoint
        var mql = window.matchMedia('(min-width: 861px)');
        var onChange = function (ev) {
            if (ev.matches && isOpen()) setOpen(false);
        };
        if (mql.addEventListener) mql.addEventListener('change', onChange);
        else if (mql.addListener) mql.addListener(onChange);
    }

    /* ============================================================
       Testimonial slider
       ============================================================ */
    var slides  = document.querySelectorAll('.testimonial-slide');
    var prevBtn = document.querySelector('[data-testimonial-prev]');
    var nextBtn = document.querySelector('[data-testimonial-next]');
    if (slides.length && prevBtn && nextBtn) {
        var current = 0;
        var show = function (idx) {
            slides.forEach(function (s, i) { s.classList.toggle('is-active', i === idx); });
        };
        prevBtn.addEventListener('click', function () {
            current = (current - 1 + slides.length) % slides.length;
            show(current);
        });
        nextBtn.addEventListener('click', function () {
            current = (current + 1) % slides.length;
            show(current);
        });
    }

    /* ============================================================
       Contact form (mailto fallback)
       ============================================================ */
    var form = document.querySelector('.contact-form-card form');
    if (form) {
        var status = form.querySelector('.form-status');
        var submitBtn = form.querySelector('button[type="submit"]');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (status) { status.textContent = ''; status.className = 'form-status'; }

            var data = new FormData(form);
            var name = (data.get('name') || '').toString().trim();
            var email = (data.get('email') || '').toString().trim();
            var message = (data.get('message') || '').toString().trim();
            var company = (data.get('company') || '').toString().trim();
            var consent = form.querySelector('input[name="consent"]');

            var emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!name || !email || !message) {
                if (status) { status.textContent = 'Bitte fülle Name, E-Mail und Nachricht aus.'; status.classList.add('is-error'); }
                return;
            }
            if (!emailRx.test(email)) {
                if (status) { status.textContent = 'Bitte gib eine gültige E-Mail-Adresse an.'; status.classList.add('is-error'); }
                return;
            }
            if (consent && !consent.checked) {
                if (status) { status.textContent = 'Bitte stimme der Datenschutzerklärung zu.'; status.classList.add('is-error'); }
                return;
            }

            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Wird gesendet …'; }

            var subject = 'Neue Anfrage von ' + name;
            var bodyLines = [
                'Name: ' + name,
                'E-Mail: ' + email,
                company ? ('Unternehmen: ' + company) : null,
                '',
                'Nachricht:',
                message
            ].filter(Boolean);
            var mailto = 'mailto:hallo@dodidis-media.de?subject=' + encodeURIComponent(subject) +
                         '&body=' + encodeURIComponent(bodyLines.join('\n'));

            window.location.href = mailto;

            if (status) {
                status.textContent = 'Dein E-Mail-Programm öffnet sich – bitte sende die vorbereitete Nachricht ab.';
                status.classList.add('is-success');
            }
            setTimeout(function () {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Erstgespräch anfragen'; }
            }, 1500);
        });
    }
})();
