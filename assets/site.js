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
       Agency phone — sticky scroll-story extraction
       Desktop only. Cards are driven by section progress and CSS
       custom properties, making the motion perfectly reversible.
       ============================================================ */
    function initPhoneScrollStory() {
        var section = document.querySelector('[data-phone-scroll-story]');
        if (!section) return;

        var cards = Array.prototype.slice.call(section.querySelectorAll('.phone-extract-card'));
        var content = section.querySelector('[data-phone-content]');
        if (!cards.length) return;

        var mqDesktop = window.matchMedia('(min-width: 1024px)');
        var ticking = false;
        var lastProgress = -1;
        var ranges = [
            [0.15, 0.35],
            [0.35, 0.55],
            [0.55, 0.75],
            [0.75, 0.95]
        ];

        function clamp(value, min, max) {
            return value < min ? min : value > max ? max : value;
        }

        function easeOutCubic(value) {
            return 1 - Math.pow(1 - value, 3);
        }

        function applyCardProgress(card, progress) {
            var x = -120 * (1 - progress);
            var y = 80 * (1 - progress);
            var scale = 0.72 + (0.28 * progress);

            card.style.setProperty('--extract-progress', progress.toFixed(4));
            card.style.setProperty('--extract-opacity', progress.toFixed(4));
            card.style.setProperty('--extract-x', x.toFixed(2) + 'px');
            card.style.setProperty('--extract-y', y.toFixed(2) + 'px');
            card.style.setProperty('--extract-scale', scale.toFixed(4));
        }

        function setCardProgress(forceVisible) {
            cards.forEach(function (card) {
                applyCardProgress(card, forceVisible ? 1 : 0);
            });
        }

        function resetInlineMotion(forceVisible) {
            setCardProgress(forceVisible);
            if (content) content.style.setProperty('--screen-y', '0px');
            lastProgress = -1;
        }

        function update() {
            ticking = false;

            if (!mqDesktop.matches || prefersReduced) {
                resetInlineMotion(true);
                return;
            }

            var rect = section.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var total = section.offsetHeight - vh;
            var progress = total > 0 ? clamp(-rect.top / total, 0, 1) : 0;

            if (Math.abs(progress - lastProgress) < 0.001) return;
            lastProgress = progress;

            cards.forEach(function (card, index) {
                var range = ranges[index] || ranges[ranges.length - 1];
                var local = clamp((progress - range[0]) / (range[1] - range[0]), 0, 1);
                var eased = easeOutCubic(local);
                applyCardProgress(card, eased);
            });

            if (content) content.style.setProperty('--screen-y', '0px');
        }

        function requestUpdate() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }

        function onResize() {
            resetInlineMotion(!mqDesktop.matches || prefersReduced);
            requestUpdate();
        }

        resetInlineMotion(!mqDesktop.matches || prefersReduced);
        update();
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', onResize);
        if (mqDesktop.addEventListener) {
            mqDesktop.addEventListener('change', onResize);
        } else if (mqDesktop.addListener) {
            mqDesktop.addListener(onResize);
        }
    }

    initPhoneScrollStory();

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
