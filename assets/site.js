(function () {
    var prefersReduced = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ============================================================
       Scroll reveal — IntersectionObserver, no library
       ============================================================ */
    var revealTargets = document.querySelectorAll(
        '[data-reveal], [data-reveal-stagger], [data-reveal-lines], [data-reveal-soft], .reveal'
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
       Scroll-driven outline+solid text banner
       Maps section-in-view (0..1) to --banner-progress on each line
       so they drift in/out as the user scrolls past.
       ============================================================ */
    (function () {
        var banner = document.querySelector('[data-scroll-banner]');
        if (!banner) return;
        var lines = banner.querySelectorAll('[data-banner-line]');
        if (!lines.length) return;

        if (prefersReduced) {
            lines.forEach(function (line) {
                line.style.setProperty('--banner-progress', '1');
            });
            return;
        }

        var ticking = false;

        function clamp(v, a, b) { return v < a ? a : v > b ? b : v; }

        function update() {
            ticking = false;
            var rect = banner.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            // 0 when banner enters from below, 1 when it exits from above.
            var raw = (vh - rect.top) / (vh + rect.height);
            var p = clamp(raw, 0, 1);
            lines.forEach(function (line) {
                line.style.setProperty('--banner-progress', p.toFixed(4));
            });
        }

        function requestUpdate() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }

        update();
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
    })();

    /* ============================================================
       Hero parallax — gentle translation on scroll for the founder
       image, mirroring the brandcontent.de hero treatment.
       ============================================================ */
    (function () {
        var heroSection = document.querySelector('[data-hero-parallax]');
        var heroImage = heroSection && heroSection.querySelector('[data-hero-image]');
        if (!heroSection || !heroImage || prefersReduced) return;

        var ticking = false;

        function update() {
            ticking = false;
            var rect = heroSection.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            // -1 (above viewport) → 0 (centered) → 1 (below viewport)
            var center = rect.top + rect.height / 2;
            var rel = (center - vh / 2) / vh;
            var clamped = rel < -1 ? -1 : rel > 1 ? 1 : rel;
            // Negative scroll → image drifts up; positive → drifts down.
            var translate = -clamped * 28;
            heroImage.style.setProperty('--hero-parallax-y', translate.toFixed(2) + 'px');
        }

        function requestUpdate() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }

        update();
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
    })();

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
            if (navWrap) navWrap.classList.toggle('is-menu-open', open);
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
       Section uses position: sticky over a tall container so the
       page visually "pauses" while cards are unpacked. Each card
       owns a slice of section progress; ranges are tuned so all
       four cards finish unpacking before the pin releases, giving
       a perfectly reversible scroll-driven choreography on both
       desktop and mobile.
       ============================================================ */
    function initPhoneScrollStory() {
        var section = document.querySelector('[data-phone-scroll-story]');
        if (!section) return;

        var cards = Array.prototype.slice.call(section.querySelectorAll('.phone-extract-card'));
        var content = section.querySelector('[data-phone-content]');
        if (!cards.length) return;

        var mqMobile = window.matchMedia('(max-width: 1023px)');
        var ticking = false;
        var lastProgress = -1;

        // Five analytics cards unpack in overlapping scroll slices. The last
        // card finishes at 0.90, leaving a 10% buffer before the pin releases
        // so the scroll handoff back to the page feels seamless.
        var ranges = [
            [0.04, 0.24],
            [0.20, 0.40],
            [0.36, 0.56],
            [0.52, 0.72],
            [0.68, 0.90]
        ];

        function clamp(value, min, max) {
            return value < min ? min : value > max ? max : value;
        }

        function easeOutCubic(value) {
            return 1 - Math.pow(1 - value, 3);
        }

        var cardsContainer = section.querySelector('.phone-extracted-cards');

        function applyCardDesktop(card, progress) {
            var side = card.getAttribute('data-side');
            // Left-column cards travel from behind the phone (positive X)
            // out to the left; right-column cards travel out to the right.
            var dir = side === 'right' ? 1 : -1;
            var distance = 220;
            var x = -dir * distance * (1 - progress);
            var y = 60 * (1 - progress);
            var scale = 0.78 + (0.22 * progress);

            card.style.setProperty('--extract-progress', progress.toFixed(4));
            card.style.setProperty('--extract-opacity', progress.toFixed(4));
            card.style.setProperty('--extract-x', x.toFixed(2) + 'px');
            card.style.setProperty('--extract-y', y.toFixed(2) + 'px');
            card.style.setProperty('--extract-scale', scale.toFixed(4));
        }

        // Mobile carousel: cards live in a single absolutely-positioned
        // slot below the phone. Each card has an enter phase (rises +
        // fades in) and an exit phase (fades up & out) when the next
        // card starts entering. Result: one card visible at a time,
        // smooth crossfade — fits viewport, reads cleanly on small
        // screens. The last card has no exit so it stays at the end.
        function applyCardMobile(card, index, totalProgress) {
            var range = ranges[index];
            var enterRaw = clamp((totalProgress - range[0]) / (range[1] - range[0]), 0, 1);
            var enter = easeOutCubic(enterRaw);

            var exit = 0;
            if (index < cards.length - 1) {
                var nextRange = ranges[index + 1];
                // Exit accelerates so this card is gone before the next
                // is fully in (uses 60% of next card's enter window).
                var exitWindow = (nextRange[1] - nextRange[0]) * 0.6;
                var exitRaw = clamp((totalProgress - nextRange[0]) / exitWindow, 0, 1);
                exit = easeOutCubic(exitRaw);
            }

            var opacity = enter * (1 - exit);
            // Rise from below (+50px) to 0, then drift up (-30px) on exit.
            var y = 50 * (1 - enter) - 30 * exit;
            var scale = 0.92 + (0.08 * enter) - (0.04 * exit);

            card.style.setProperty('--extract-progress', enter.toFixed(4));
            card.style.setProperty('--extract-opacity', opacity.toFixed(4));
            card.style.setProperty('--extract-x', '0px');
            card.style.setProperty('--extract-y', y.toFixed(2) + 'px');
            card.style.setProperty('--extract-scale', scale.toFixed(4));

            return { enter: enter, exit: exit, active: enter * (1 - exit) };
        }

        function applyCardProgress(card, progress) {
            applyCardDesktop(card, progress);
        }

        function setCardProgress(forceVisible) {
            cards.forEach(function (card, index) {
                if (mqMobile.matches) {
                    // On mobile we want only the LAST card visible at full
                    // forceVisible (final state), or all hidden at 0.
                    if (forceVisible) {
                        applyCardMobile(card, index, 1);
                    } else {
                        applyCardMobile(card, index, 0);
                    }
                } else {
                    applyCardDesktop(card, forceVisible ? 1 : 0);
                }
            });
        }

        function resetInlineMotion(forceVisible) {
            setCardProgress(forceVisible);
            if (content) content.style.setProperty('--screen-y', '0px');
            lastProgress = -1;
        }

        function updateIndicator(states) {
            if (!cardsContainer) return;
            // Map each card's "active" weight to a CSS variable so the
            // 5-dot indicator below the slot lights up the current step.
            for (var i = 0; i < states.length; i++) {
                var weight = 0.18 + (states[i] * 0.82);
                cardsContainer.style.setProperty('--carousel-d' + (i + 1), weight.toFixed(3));
            }
        }

        function update() {
            ticking = false;

            if (prefersReduced) {
                resetInlineMotion(true);
                return;
            }

            var rect = section.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var total = section.offsetHeight - vh;
            var progress = total > 0 ? clamp(-rect.top / total, 0, 1) : 0;

            if (Math.abs(progress - lastProgress) < 0.001) return;
            lastProgress = progress;

            var mobile = mqMobile.matches;
            var states = [];

            cards.forEach(function (card, index) {
                if (mobile) {
                    var s = applyCardMobile(card, index, progress);
                    states.push(s.active);
                } else {
                    var range = ranges[index] || ranges[ranges.length - 1];
                    var local = clamp((progress - range[0]) / (range[1] - range[0]), 0, 1);
                    applyCardDesktop(card, easeOutCubic(local));
                }
            });

            if (mobile) updateIndicator(states);

            if (content) content.style.setProperty('--screen-y', '0px');
        }

        function requestUpdate() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }

        function onResize() {
            resetInlineMotion(prefersReduced);
            requestUpdate();
        }

        resetInlineMotion(prefersReduced);
        update();
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', onResize);
        window.addEventListener('orientationchange', onResize);
        if (mqMobile.addEventListener) {
            mqMobile.addEventListener('change', onResize);
        } else if (mqMobile.addListener) {
            mqMobile.addListener(onResize);
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
       Logo marquee — clone track once for seamless infinite loop
       ============================================================ */
    (function () {
        var marquees = document.querySelectorAll('[data-logos-marquee]');
        if (!marquees.length) return;
        marquees.forEach(function (mq) {
            var track = mq.querySelector('.logos-track');
            if (!track || track.dataset.cloned === 'true') return;
            var items = Array.prototype.slice.call(track.children);
            items.forEach(function (item) {
                var clone = item.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                track.appendChild(clone);
            });
            track.dataset.cloned = 'true';
        });
    })();

    /* ============================================================
       Hebel (lever) — scroll-driven tilt + glow particles
       Maps section visibility (0..1) to --lever-tilt and --lever-glow
       ============================================================ */
    (function () {
        var stage = document.querySelector('[data-hebel-stage]');
        if (!stage) return;

        var section = stage.closest('section') || stage;
        var ticking = false;
        var active = false;

        function clamp(v, a, b) { return v < a ? a : v > b ? b : v; }

        function update() {
            ticking = false;
            if (!active || prefersReduced) return;
            var rect = section.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            // 0 when section bottom hits viewport top, 1 when section top reaches bottom
            var raw = (vh - rect.top) / (vh + rect.height);
            var p = clamp(raw, 0, 1);
            // Map progress to tilt: -10deg (left heavy) → +10deg (right heavy)
            var tilt = -10 + (p * 20);
            stage.style.setProperty('--lever-tilt', tilt.toFixed(2) + 'deg');
            stage.style.setProperty('--lever-glow', p.toFixed(3));
        }

        function requestUpdate() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }

        if ('IntersectionObserver' in window) {
            var obs = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    active = entry.isIntersecting;
                    if (active) requestUpdate();
                });
            }, { threshold: [0, 0.05, 0.5, 0.95, 1] });
            obs.observe(section);
        } else {
            active = true;
        }

        if (prefersReduced) {
            stage.style.setProperty('--lever-tilt', '5deg');
            stage.style.setProperty('--lever-glow', '0.7');
            return;
        }

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        requestUpdate();
    })();

    /* ============================================================
       Number count-up — animates [data-count-up] when in view.
       Drives Stage-4 "+168%" / "+42%" reveals + the stats row.
       ============================================================ */
    (function () {
        var targets = document.querySelectorAll('[data-count-up]');
        if (!targets.length) return;

        function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

        function format(value, decimals) {
            if (decimals > 0) return value.toFixed(decimals).replace('.', ',');
            return Math.round(value).toString();
        }

        function run(el) {
            var target = parseFloat(el.getAttribute('data-count-up'));
            if (isNaN(target)) return;
            var decimals = parseInt(el.getAttribute('data-count-decimals') || '0', 10);
            var prefix = el.getAttribute('data-count-prefix') || '';
            var suffix = el.getAttribute('data-count-suffix') || '';
            var duration = parseInt(el.getAttribute('data-count-duration') || '1600', 10);
            var start = performance.now();

            function tick(now) {
                var p = Math.min((now - start) / duration, 1);
                var v = easeOutCubic(p) * target;
                el.textContent = prefix + format(v, decimals) + suffix;
                if (p < 1) requestAnimationFrame(tick);
                else el.textContent = prefix + format(target, decimals) + suffix;
            }
            requestAnimationFrame(tick);
        }

        if (prefersReduced || !('IntersectionObserver' in window)) {
            // Leave original markup intact.
            return;
        }

        // Reset to zero on init so the count is visually felt when it triggers.
        targets.forEach(function (el) {
            var prefix = el.getAttribute('data-count-prefix') || '';
            var suffix = el.getAttribute('data-count-suffix') || '';
            var decimals = parseInt(el.getAttribute('data-count-decimals') || '0', 10);
            el.textContent = prefix + (decimals > 0 ? '0,' + '0'.repeat(decimals) : '0') + suffix;
        });

        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    run(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4, rootMargin: '0px 0px -10% 0px' });
        targets.forEach(function (el) { io.observe(el); });
    })();

    /* ============================================================
       Chart draw-in — measures path length, sets dashoffset,
       triggers .is-drawn class on viewport entry.
       ============================================================ */
    (function () {
        var charts = document.querySelectorAll('[data-chart-draw]');
        if (!charts.length) return;

        charts.forEach(function (svg) {
            var path = svg.querySelector('[data-draw-path]');
            if (!path || typeof path.getTotalLength !== 'function') return;
            var length = Math.ceil(path.getTotalLength());
            // Buffer of +2 prevents subpixel "tip" artefacts on certain GPUs.
            svg.style.setProperty('--draw-length', (length + 2));
        });

        if (prefersReduced || !('IntersectionObserver' in window)) {
            charts.forEach(function (svg) { svg.classList.add('is-drawn'); });
            return;
        }

        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-drawn');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.35, rootMargin: '0px 0px -8% 0px' });
        charts.forEach(function (svg) { io.observe(svg); });
    })();

    /* ============================================================
       Phone entrance — scale 0.9 → 1 + fade once stage is in view.
       Independent from the existing scrollytelling pin handler.
       ============================================================ */
    (function () {
        var stages = document.querySelectorAll('[data-phone-entrance]');
        if (!stages.length) return;

        if (prefersReduced || !('IntersectionObserver' in window)) {
            stages.forEach(function (s) { s.classList.add('is-revealed'); });
            return;
        }

        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2, rootMargin: '0px 0px -5% 0px' });
        stages.forEach(function (s) { io.observe(s); });
    })();

    /* ============================================================
       CTA glow — pulses .btn-primary while #kontakt is in view.
       Toggles body.is-cta-active so headline & footer button both
       benefit; pause on hover (CSS).
       ============================================================ */
    (function () {
        var contact = document.getElementById('kontakt');
        if (!contact) return;

        if (prefersReduced || !('IntersectionObserver' in window)) return;

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                document.body.classList.toggle('is-cta-active', entry.isIntersecting);
            });
        }, { threshold: 0.18 });
        io.observe(contact);
    })();

    /* ============================================================
       Hero depth parallax — second layer for the glow halo.
       Cutout already drifts via [data-hero-image]; here the glow
       moves at ~0.35x speed to fake atmospheric depth.
       ============================================================ */
    (function () {
        var heroSection = document.querySelector('[data-hero-parallax]');
        var bg = heroSection && heroSection.querySelector('[data-hero-bg]');
        if (!heroSection || !bg || prefersReduced) return;

        var ticking = false;

        function update() {
            ticking = false;
            var rect = heroSection.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var center = rect.top + rect.height / 2;
            var rel = (center - vh / 2) / vh;
            var clamped = rel < -1 ? -1 : rel > 1 ? 1 : rel;
            // Background drifts slower (smaller amplitude) than the cutout's 28px.
            var translate = -clamped * 10;
            bg.style.setProperty('--hero-bg-y', translate.toFixed(2) + 'px');
        }

        function requestUpdate() {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(update);
        }

        update();
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
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
