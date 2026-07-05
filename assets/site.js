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
                line.style.setProperty('--banner-x', '0px');
            });
            return;
        }

        var ticking = false;

        function clamp(v, a, b) { return v < a ? a : v > b ? b : v; }

        function update() {
            ticking = false;
            var rect = banner.getBoundingClientRect();
            var vh = window.innerHeight || document.documentElement.clientHeight;
            var vw = window.innerWidth || document.documentElement.clientWidth;
            // 0 when banner enters from below, 1 when it exits from above.
            var raw = (vh - rect.top) / (vh + rect.height);
            // Amplify around the center so the transition completes in a much
            // narrower scroll window — the banner snaps into place faster.
            var p = clamp(0.5 + (raw - 0.5) * 2.8, 0, 1);
            var amp = vw * 0.30;
            lines.forEach(function (line) {
                // Drive a whole-pixel transform instead of a fractional vw value.
                // Sub-pixel transforms re-rasterise the -webkit-text-stroke outline
                // every frame, which made the "UNSICHTBAR" letters shimmer/shift.
                var dir = line.getAttribute('data-banner-line') === 'left' ? (1 - p) : (p - 1);
                line.style.setProperty('--banner-x', Math.round(dir * amp) + 'px');
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
            // Stärkere Drift + Skalierung: beim Reinscrollen "kommen" die Gründer
            // leicht nach vorne, beim Wegscrollen driften sie hoch und schrumpfen.
            var translate = -clamped * 46;
            var scale = 1 + (1 - Math.min(Math.abs(clamped), 1)) * 0.07;
            heroImage.style.setProperty('--hero-parallax-y', translate.toFixed(2) + 'px');
            heroImage.style.setProperty('--hero-scale', scale.toFixed(3));
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
       Hero-Gründer: zwei getrennte Bilder statt des kombinierten.
       Solange NICHT beide Einzelbilder (hero-raphael/-timo) existieren,
       bleibt das kombinierte Fallback-Bild sichtbar. Erst wenn beide
       erfolgreich laden, wird auf die Zwei-Bild-Ansicht umgeschaltet
       (src wird erst dann gesetzt → keine kaputten Bild-Icons).
       ============================================================ */
    (function () {
        var stage = document.querySelector('[data-hero-image]');
        var split = stage && stage.querySelector('[data-hero-split]');
        var fallback = stage && stage.querySelector('[data-hero-fallback]');
        if (!stage || !split || !fallback) return;

        var imgs = Array.prototype.slice.call(split.querySelectorAll('img[data-src]'));
        if (!imgs.length) return;

        var loaded = 0;
        var failed = false;

        function reveal() {
            imgs.forEach(function (img) { img.src = img.getAttribute('data-src'); });
            split.hidden = false;
            split.setAttribute('aria-hidden', 'false');
            fallback.hidden = true;
            fallback.setAttribute('aria-hidden', 'true');
            stage.classList.add('is-split');
        }

        imgs.forEach(function (img) {
            var probe = new Image();
            probe.onload = function () {
                loaded++;
                if (loaded === imgs.length && !failed) reveal();
            };
            probe.onerror = function () { failed = true; };
            probe.src = img.getAttribute('data-src');
        });
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
            var items = group.querySelectorAll('details.faq-item, details.service-acc');
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
       Team avatars — try each candidate file (handles .JPEG/.jpeg/.jpg
       case differences) and fall back to the initials if none load.
       ============================================================ */
    (function () {
        var imgs = document.querySelectorAll('[data-team-img]');
        if (!imgs.length) return;
        imgs.forEach(function (img) {
            var candidates = (img.getAttribute('data-candidates') || '')
                .split(',')
                .map(function (s) { return s.trim(); })
                .filter(Boolean);
            var i = 0;
            function tryNext() {
                if (i >= candidates.length) {
                    img.style.display = 'none';
                    if (img.parentNode) img.parentNode.classList.add('is-fallback');
                    return;
                }
                img.src = candidates[i++];
            }
            img.addEventListener('error', tryNext);
            img.addEventListener('load', function () {
                img.style.display = '';
                if (img.parentNode) img.parentNode.classList.remove('is-fallback');
            });
            tryNext();
        });
    })();

    /* ============================================================
       Thought bubbles — painpoint scenarios. Each bubble toggles
       its own text open; clicking one closes the others in the group.
       ============================================================ */
    (function () {
        var groups = document.querySelectorAll('[data-thought-group]');
        if (!groups.length) return;
        groups.forEach(function (group) {
            var bubbles = group.querySelectorAll('.thought-bubble');
            bubbles.forEach(function (bubble) {
                bubble.addEventListener('click', function () {
                    var willOpen = bubble.getAttribute('aria-expanded') !== 'true';
                    bubbles.forEach(function (other) {
                        other.setAttribute('aria-expanded', 'false');
                        other.classList.remove('is-open');
                    });
                    if (willOpen) {
                        bubble.setAttribute('aria-expanded', 'true');
                        bubble.classList.add('is-open');
                    }
                });
            });
        });
    })();

    /* ============================================================
       Logo marquee — fill the row first (so few logos don't leave big
       gaps), then duplicate the whole set once for a seamless -50% loop.
       ============================================================ */
    (function () {
        var marquees = document.querySelectorAll('[data-logos-marquee]');
        if (!marquees.length) return;
        marquees.forEach(function (mq) {
            var track = mq.querySelector('.logos-track');
            if (!track || track.dataset.cloned === 'true') return;
            var baseItems = Array.prototype.slice.call(track.children);
            if (!baseItems.length) return;

            // 1) Originalset so oft wiederholen, bis die Reihe mind. die
            //    Marquee-Breite füllt (wichtig bei wenigen Logos).
            var guard = 0;
            while (track.scrollWidth < mq.offsetWidth && guard < 50) {
                baseItems.forEach(function (item) {
                    track.appendChild(item.cloneNode(true));
                });
                guard++;
            }

            // 2) Die gefüllte Reihe einmal komplett duplizieren -> -50%-Loop
            //    ist nahtlos und die Reihe ist nie leer.
            Array.prototype.slice.call(track.children).forEach(function (item) {
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
            // Map progress to tilt: -22deg (left heavy) → +22deg (right heavy).
            // Steeper range so the lever visibly "weighs more" as it swings.
            var tilt = -22 + (p * 44);
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
            stage.style.setProperty('--lever-tilt', '12deg');
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

            // Anfrage direkt ins CRM schreiben (statt mailto). Honeypot-Feld
            // „website" wird mitgesendet; echte Nutzer lassen es leer.
            fetch('agenturtool/api/contact_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    company: company,
                    message: message,
                    consent: (consent && consent.checked) ? '1' : '',
                    website: (data.get('website') || '').toString()
                })
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (j) {
                    return { ok: res.ok, body: j };
                });
            }).then(function (r) {
                if (!r.ok || !r.body || !r.body.ok) {
                    throw new Error((r.body && r.body.error) || 'Senden fehlgeschlagen.');
                }
                form.reset();
                if (status) {
                    status.textContent = 'Danke! Deine Anfrage ist bei uns eingegangen – wir melden uns zeitnah.';
                    status.classList.add('is-success');
                }
            }).catch(function (err) {
                if (status) {
                    status.textContent = 'Senden hat nicht geklappt: ' + err.message +
                        ' Schreib uns gern direkt an kontakt@dodidis-media.de.';
                    status.classList.add('is-error');
                }
            }).then(function () {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Kostenloses Erstgespräch anfragen'; }
            });
        });
    }
})();
