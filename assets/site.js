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
       Agency phone — scroll-progress driven transforms
       Uses requestAnimationFrame, transforms only, sticky-section
       progress in the [0..1] range.
       ============================================================ */
    (function () {
        var stage   = document.querySelector('[data-phone-stage]');
        var mockup  = document.querySelector('[data-phone-mockup]');
        var content = document.querySelector('[data-phone-content]');
        if (!stage || !mockup || !content) return;
        if (prefersReduced) return;

        var section = stage.closest('.agency-phone-section') || stage.parentElement;
        if (!section) return;

        // Skip on mobile — sticky disabled, animation would be jumpy
        var mqMobile = window.matchMedia('(max-width: 860px)');
        var isMobile = mqMobile.matches;

        var ticking = false;
        var lastProgress = -1;
        var contentScrollMax = 0;

        function measureContentMax() {
            var screen = mockup.querySelector('.agency-phone-screen');
            if (!screen) { contentScrollMax = 0; return; }
            var diff = content.scrollHeight - screen.clientHeight;
            contentScrollMax = diff > 0 ? Math.min(diff, 220) : 0;
        }

        function clamp(v, a, b) { return v < a ? a : v > b ? b : v; }

        function update() {
            ticking = false;
            if (isMobile) return;

            var rect = section.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;

            // Progress: 0 when section just appears at the bottom of viewport,
            // 1 when the section's bottom leaves the top.
            var totalRange = rect.height + vh;
            var travelled = vh - rect.top;
            var p = clamp(travelled / totalRange, 0, 1);

            if (Math.abs(p - lastProgress) < 0.0015) return;
            lastProgress = p;

            // Smooth easing — keep changes subtle
            var eased = p; // linear is fine for sticky-driven motion

            var translateY = (eased - 0.5) * 24;          // -12px .. +12px
            var scale = 0.96 + eased * 0.07;              // 0.96 .. 1.03
            var rotate = (eased - 0.5) * 3.2;             // -1.6deg .. +1.6deg (well under 4°)
            var screenY = -eased * contentScrollMax;      // counter-scroll

            mockup.style.setProperty('--phone-y', translateY.toFixed(2) + 'px');
            mockup.style.setProperty('--phone-scale', scale.toFixed(3));
            mockup.style.setProperty('--phone-rot', rotate.toFixed(2) + 'deg');
            content.style.setProperty('--screen-y', screenY.toFixed(2) + 'px');
        }

        function onScroll() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }

        function onResize() {
            isMobile = mqMobile.matches;
            if (isMobile) {
                // reset transforms cleanly
                mockup.style.setProperty('--phone-y', '0px');
                mockup.style.setProperty('--phone-scale', '1');
                mockup.style.setProperty('--phone-rot', '0deg');
                content.style.setProperty('--screen-y', '0px');
            }
            measureContentMax();
            lastProgress = -1;
            onScroll();
        }

        measureContentMax();
        update();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onResize);
        if (mqMobile.addEventListener) {
            mqMobile.addEventListener('change', onResize);
        } else if (mqMobile.addListener) {
            mqMobile.addListener(onResize);
        }
    })();

    /* ============================================================
       Reveal-scale helper — uses the same observer pattern
       ============================================================ */
    (function () {
        var scaleTargets = document.querySelectorAll('[data-reveal-scale]');
        if (!scaleTargets.length) return;
        if (prefersReduced || !('IntersectionObserver' in window)) {
            scaleTargets.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.18, rootMargin: '0px 0px -6% 0px' });
        scaleTargets.forEach(function (el) { io.observe(el); });
    })();

    /* ============================================================
       FAQ accordion — uses native <details> if present,
       single-open behaviour within a [data-faq] group.
       ============================================================ */
    (function () {
        var groups = document.querySelectorAll('[data-faq]');
        if (!groups.length) return;
        groups.forEach(function (group) {
            var items = group.querySelectorAll('details.faq-item');
            items.forEach(function (item) {
                item.addEventListener('toggle', function () {
                    if (item.open) {
                        items.forEach(function (other) {
                            if (other !== item && other.open) other.open = false;
                        });
                    }
                });
            });
        });
    })();

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
