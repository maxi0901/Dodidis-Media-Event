<?php
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="description" content="Dodidis.Media – Social Media Marketing Agentur. Strategischer Content, der auffällt. Reels, die performen. Kampagnen, die verkaufen.">
    <title>Dodidis.Media – Social Media Marketing Agentur</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0B0F14;
            --bg-secondary: #121821;
            --glass-bg: rgba(53, 143, 129, 0.12);
            --glass-bg-strong: rgba(53, 143, 129, 0.18);
            --glass-border: rgba(255, 255, 255, 0.12);
            --glass-border-strong: rgba(255, 255, 255, 0.22);
            --glass-sheen: linear-gradient(135deg, rgba(255,255,255,0.14) 0%, rgba(255,255,255,0.04) 38%, rgba(255,255,255,0) 70%);
            --glass-surface: linear-gradient(135deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.03) 55%, rgba(255,255,255,0.06) 100%);
            --glass-surface-dark: linear-gradient(135deg, rgba(28,38,48,0.55) 0%, rgba(14,20,28,0.45) 60%, rgba(20,30,40,0.55) 100%);
            --glass-inset: inset 0 1px 0 rgba(255,255,255,0.18), inset 0 -1px 0 rgba(255,255,255,0.04);
            --accent: #358F81;
            --accent-hover: #46A99A;
            --accent-soft: rgba(53, 143, 129, 0.18);
            --text-primary: #FFFFFF;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --divider: rgba(255, 255, 255, 0.05);
            --glow: rgba(53, 143, 129, 0.35);
            --radius-lg: 28px;
            --radius-md: 20px;
            --radius-sm: 14px;
            --radius-pill: 999px;
        }

        * { box-sizing: border-box; }

        html, body { margin: 0; padding: 0; }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 110px;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-primary);
            background: var(--bg-primary);
            line-height: 1.55;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background-image:
                radial-gradient(900px 600px at 85% -5%, rgba(53, 143, 129, 0.22), transparent 60%),
                radial-gradient(700px 500px at -10% 35%, rgba(70, 169, 154, 0.14), transparent 60%),
                radial-gradient(800px 600px at 50% 110%, rgba(53, 143, 129, 0.14), transparent 60%);
            background-attachment: fixed;
            position: relative;
        }

        /* ===== Ambient glass orbs ===== */
        .ambient {
            position: fixed;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
        }

        .ambient .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.55;
            mix-blend-mode: screen;
            animation: float 18s ease-in-out infinite;
        }

        .ambient .orb-1 {
            width: 520px; height: 520px;
            top: -120px; left: -120px;
            background: radial-gradient(circle, rgba(70,169,154,0.55), transparent 70%);
        }
        .ambient .orb-2 {
            width: 460px; height: 460px;
            top: 35%; right: -140px;
            background: radial-gradient(circle, rgba(53,143,129,0.48), transparent 70%);
            animation-delay: -6s;
        }
        .ambient .orb-3 {
            width: 600px; height: 600px;
            bottom: -180px; left: 30%;
            background: radial-gradient(circle, rgba(70,169,154,0.35), transparent 70%);
            animation-delay: -12s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%      { transform: translate(40px, -30px) scale(1.06); }
            66%      { transform: translate(-30px, 25px) scale(0.96); }
        }

        a { color: inherit; text-decoration: none; }

        .container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 0 1.25rem;
        }

        .glass {
            position: relative;
            background: var(--glass-surface);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--glass-inset), 0 18px 48px rgba(0,0,0,0.4);
        }

        /* Glass sheen helper – wird auf Karten via ::before angewendet */
        .glass-sheen::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: var(--glass-sheen);
            pointer-events: none;
            opacity: 0.85;
        }

        .eyebrow {
            display: inline-block;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-weight: 700;
            font-size: 0.72rem;
            margin: 0 0 1rem;
        }

        h1, h2, h3, h4 {
            font-family: "Inter", sans-serif;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            margin: 0;
        }

        h1 {
            font-size: clamp(2.2rem, 5.4vw, 4rem);
            line-height: 1.05;
            font-weight: 800;
            text-wrap: balance;
        }

        h2 {
            font-size: clamp(1.7rem, 3.2vw, 2.6rem);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -0.025em;
        }

        h3 {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        p { margin: 0; }

        .accent-text { color: var(--accent); }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.55rem;
            padding: 0.95rem 1.5rem;
            border-radius: var(--radius-pill);
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease, color 0.2s ease;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%);
            color: #fff;
            box-shadow: 0 10px 30px var(--glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 38px var(--glow);
        }

        .btn-ghost {
            background: linear-gradient(135deg, rgba(255,255,255,0.10), rgba(255,255,255,0.02));
            backdrop-filter: blur(18px) saturate(160%);
            -webkit-backdrop-filter: blur(18px) saturate(160%);
            color: var(--text-primary);
            border-color: var(--glass-border-strong);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.18);
        }

        .btn-ghost:hover {
            background: linear-gradient(135deg, rgba(255,255,255,0.14), rgba(53,143,129,0.10));
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.22), 0 12px 28px rgba(53,143,129,0.22);
        }

        .btn .arrow {
            display: inline-flex;
            transition: transform 0.2s ease;
        }

        .btn:hover .arrow {
            transform: translateX(3px);
        }

        .icon-btn {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            display: inline-grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(255,255,255,0.12), rgba(255,255,255,0.02));
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            border: 1px solid var(--glass-border-strong);
            color: var(--text-primary);
            cursor: pointer;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.20);
            transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }

        .icon-btn:hover {
            border-color: var(--accent);
            background: linear-gradient(135deg, rgba(53,143,129,0.28), rgba(53,143,129,0.08));
            transform: translateY(-2px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.25), 0 10px 22px rgba(53,143,129,0.25);
        }

        /* ===== Navbar ===== */
        .nav-wrap {
            position: sticky;
            top: 1rem;
            z-index: 50;
            padding: 0 1.25rem;
        }

        .nav {
            position: relative;
            max-width: 1240px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0.85rem 0.75rem 1.4rem;
            border-radius: var(--radius-pill);
            background:
                linear-gradient(135deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.02) 50%, rgba(255,255,255,0.06) 100%),
                rgba(11, 15, 20, 0.45);
            backdrop-filter: blur(34px) saturate(180%);
            -webkit-backdrop-filter: blur(34px) saturate(180%);
            border: 1px solid var(--glass-border);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.22),
                inset 0 -1px 0 rgba(255,255,255,0.04),
                0 18px 48px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }

        .nav::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0) 55%);
            pointer-events: none;
        }

        .nav > * { position: relative; z-index: 1; }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            flex: 0 0 auto;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(53,143,129,0.25), rgba(53,143,129,0.05));
            border: 1px solid var(--glass-border-strong);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 0.9rem;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.04);
        }

        .brand-name {
            font-weight: 700;
            letter-spacing: 0.02em;
            font-size: 0.95rem;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.6rem;
            margin: 0 auto;
            list-style: none;
            padding: 0;
        }

        .nav-links a {
            position: relative;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-secondary);
            padding: 0.4rem 0.1rem;
            transition: color 0.2s ease;
        }

        .nav-links a:hover { color: var(--text-primary); }

        .nav-links a.is-active { color: var(--text-primary); }

        .nav-links a.is-active::after {
            content: "";
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: -6px;
            width: 5px;
            height: 5px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 10px var(--glow);
        }

        .nav-cta { flex: 0 0 auto; }

        .nav-toggle {
            display: none;
            width: 42px;
            height: 42px;
            background: transparent;
            border: 1px solid var(--glass-border-strong);
            border-radius: 12px;
            color: #fff;
            cursor: pointer;
            align-items: center;
            justify-content: center;
        }

        .nav-toggle svg { display: block; }

        /* ===== Hero ===== */
        main { padding-top: 2rem; }

        .hero {
            padding: 3rem 0 4rem;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .hero-headline {
            margin-top: 0.6rem;
        }

        .hero-sub {
            color: var(--text-secondary);
            font-size: 1.02rem;
            max-width: 52ch;
            margin-top: 1.4rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
            margin-top: 1.8rem;
        }

        .trust-row {
            display: flex;
            align-items: center;
            gap: 0.95rem;
            margin-top: 2.5rem;
        }

        .avatars {
            display: flex;
            align-items: center;
        }

        .avatars .av {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 2px solid var(--bg-primary);
            background: linear-gradient(135deg, #2a3a4a, #1a242e);
            margin-left: -10px;
            display: grid;
            place-items: center;
            color: var(--text-secondary);
            font-size: 0.78rem;
            font-weight: 700;
        }

        .avatars .av:first-child { margin-left: 0; }
        .avatars .av:nth-child(1) { background: linear-gradient(135deg, #2f6c63, #1a3a35); color: #fff; }
        .avatars .av:nth-child(2) { background: linear-gradient(135deg, #3a4d5a, #1f2b34); color: #fff; }
        .avatars .av:nth-child(3) { background: linear-gradient(135deg, #46a99a, #2a6b62); color: #fff; }

        .trust-text {
            color: var(--text-secondary);
            font-size: 0.92rem;
            line-height: 1.4;
        }

        .trust-text strong { color: var(--text-primary); font-weight: 700; }
        .trust-text .accent-text { font-weight: 700; }

        /* Hero Right */
        .hero-visual {
            position: relative;
            aspect-ratio: 5 / 4;
            border-radius: var(--radius-lg);
            overflow: hidden;
            border: 1px solid var(--glass-border-strong);
            background:
                radial-gradient(circle at 30% 25%, rgba(53,143,129,0.22), transparent 55%),
                radial-gradient(circle at 80% 35%, rgba(70, 169, 154, 0.14), transparent 55%),
                linear-gradient(135deg, #1a2128 0%, #0e1318 60%, #0a0f14 100%);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.18),
                inset 0 -1px 0 rgba(255,255,255,0.04),
                0 30px 80px rgba(0,0,0,0.55);
        }

        /* simulated studio: two vertical light bars + soft grain */
        .hero-visual::before {
            content: "";
            position: absolute;
            top: 8%;
            left: 38%;
            width: 6%;
            height: 55%;
            background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(70,169,154,0.5) 60%, transparent);
            filter: blur(6px);
            opacity: 0.55;
            border-radius: 4px;
        }
        .hero-visual::after {
            content: "";
            position: absolute;
            top: 12%;
            left: 50%;
            width: 6%;
            height: 50%;
            background: linear-gradient(180deg, rgba(255,255,255,0.7), rgba(53,143,129,0.4) 60%, transparent);
            filter: blur(7px);
            opacity: 0.55;
            border-radius: 4px;
        }

        .hero-visual .scene {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 75% 70%, rgba(255,255,255,0.06), transparent 35%),
                repeating-linear-gradient(90deg, rgba(255,255,255,0.02) 0 1px, transparent 1px 14px);
            pointer-events: none;
        }

        .hero-visual .desk {
            position: absolute;
            left: 8%;
            right: 8%;
            bottom: 16%;
            height: 22%;
            background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
            border-top: 1px solid rgba(255,255,255,0.07);
            border-radius: 8px;
        }

        .hero-visual .silhouette {
            position: absolute;
            right: 14%;
            bottom: 22%;
            width: 70px;
            height: 130px;
            background: linear-gradient(180deg, #1d262e 0%, #0d1217 100%);
            border-radius: 30px 30px 6px 6px;
            box-shadow: -3px 0 14px rgba(0,0,0,0.4);
        }
        .hero-visual .silhouette::before {
            content: "";
            position: absolute;
            top: -28px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: #1a232b;
            border-radius: 50%;
        }

        .hero-visual .monitor {
            position: absolute;
            right: 28%;
            bottom: 30%;
            width: 90px;
            height: 60px;
            background: linear-gradient(135deg, rgba(53,143,129,0.65), rgba(70,169,154,0.25));
            border-radius: 4px;
            box-shadow: 0 0 26px rgba(53,143,129,0.45);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .coop-card {
            position: absolute;
            left: 8%;
            right: 8%;
            bottom: 6%;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.1rem 1.2rem;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.14) 0%, rgba(255,255,255,0.03) 55%, rgba(255,255,255,0.08) 100%),
                rgba(11, 15, 20, 0.45);
            backdrop-filter: blur(34px) saturate(180%);
            -webkit-backdrop-filter: blur(34px) saturate(180%);
            border: 1px solid var(--glass-border-strong);
            border-radius: var(--radius-md);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.22),
                inset 0 -1px 0 rgba(255,255,255,0.05),
                0 16px 40px rgba(0,0,0,0.45);
            overflow: hidden;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .coop-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0) 55%);
            pointer-events: none;
        }

        .coop-card > * { position: relative; z-index: 1; }

        .coop-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
            box-shadow: 0 18px 40px var(--glow);
        }

        .coop-card .coop-info { flex: 1; min-width: 0; }

        .coop-card .coop-eyebrow {
            color: var(--accent);
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
        }

        .coop-card .coop-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.01em;
            margin-bottom: 0.15rem;
        }

        .coop-card .coop-brand .link-icon {
            color: var(--accent);
            display: inline-flex;
        }

        .coop-card .coop-text {
            color: var(--text-secondary);
            font-size: 0.86rem;
        }

        /* ===== Section heads ===== */
        .section { padding: 4rem 0; }

        .section-head {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: end;
            margin-bottom: 2.5rem;
        }

        .section-head .lead { max-width: 56ch; }

        .section-head p {
            color: var(--text-secondary);
            font-size: 0.98rem;
        }

        .head-action { justify-self: end; }

        .ghost-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.92rem;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .ghost-link:hover { color: var(--accent); transform: translateX(2px); }

        /* ===== Leistungen ===== */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1.25rem;
        }

        .service-card {
            position: relative;
            padding: 1.6rem 1.5rem 1.4rem;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.02) 55%, rgba(255,255,255,0.06) 100%),
                rgba(18, 24, 33, 0.40);
            backdrop-filter: blur(28px) saturate(170%);
            -webkit-backdrop-filter: blur(28px) saturate(170%);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.18),
                inset 0 -1px 0 rgba(255,255,255,0.04),
                0 14px 36px rgba(0,0,0,0.35);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            overflow: hidden;
            isolation: isolate;
            transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
        }

        .service-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0) 50%);
            pointer-events: none;
            z-index: 0;
        }

        .service-card::after {
            content: "";
            position: absolute;
            top: -40%;
            left: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(closest-side, rgba(70,169,154,0.35), transparent 70%);
            filter: blur(40px);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 0;
        }

        .service-card > * { position: relative; z-index: 1; }

        .service-card:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            background:
                linear-gradient(135deg, rgba(255,255,255,0.14) 0%, rgba(53,143,129,0.10) 60%, rgba(70,169,154,0.08) 100%),
                rgba(18, 24, 33, 0.40);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.25),
                0 24px 56px rgba(53,143,129,0.28);
        }

        .service-card:hover::after { opacity: 1; }

        .service-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background:
                linear-gradient(135deg, rgba(70,169,154,0.30), rgba(53,143,129,0.10)),
                rgba(255,255,255,0.04);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--glass-border-strong);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.22), 0 6px 18px rgba(53,143,129,0.18);
            display: grid;
            place-items: center;
            color: var(--accent);
        }

        .service-card h3 { font-size: 1.05rem; }

        .service-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .service-arrow {
            margin-top: auto;
            color: var(--accent);
            display: inline-flex;
        }

        /* ===== Stats ===== */
        .stats {
            position: relative;
            margin-top: 2rem;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            padding: 1.6rem 0.75rem;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.02) 55%, rgba(255,255,255,0.06) 100%),
                rgba(18, 24, 33, 0.40);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.20),
                inset 0 -1px 0 rgba(255,255,255,0.04),
                0 18px 44px rgba(0,0,0,0.4);
            overflow: hidden;
        }

        .stats::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0) 55%);
            pointer-events: none;
        }

        .stats > * { position: relative; z-index: 1; }

        .stat {
            display: flex;
            align-items: center;
            gap: 0.95rem;
            padding: 0.4rem 1.2rem;
            position: relative;
        }

        .stat + .stat::before {
            content: "";
            position: absolute;
            left: 0;
            top: 18%;
            bottom: 18%;
            width: 1px;
            background: var(--divider);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            background:
                linear-gradient(135deg, rgba(70,169,154,0.32), rgba(53,143,129,0.10)),
                rgba(255,255,255,0.04);
            border: 1px solid var(--glass-border-strong);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.20), 0 6px 18px rgba(53,143,129,0.20);
            color: var(--accent);
            display: grid;
            place-items: center;
            flex: 0 0 auto;
        }

        .stat-num {
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--accent);
            letter-spacing: -0.02em;
            text-shadow: 0 0 22px rgba(53,143,129,0.35);
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.25;
            margin-top: 0.25rem;
        }

        /* ===== Projekte ===== */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            position: relative;
        }

        .project {
            position: relative;
            aspect-ratio: 4 / 5;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--glass-border);
            background: var(--bg-secondary);
            cursor: pointer;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .project:hover {
            transform: translateY(-4px);
            border-color: var(--accent);
            box-shadow: 0 24px 48px rgba(0,0,0,0.55);
        }

        .project .img {
            position: absolute;
            inset: 0;
            transition: transform 0.4s ease, filter 0.4s ease;
        }

        .project:hover .img {
            transform: scale(1.05);
            filter: brightness(1.05);
        }

        .project .img::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.7) 100%);
        }

        .project .label {
            position: absolute;
            left: 0.95rem;
            bottom: 0.95rem;
            padding: 0.5rem 0.95rem;
            border-radius: 999px;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.18), rgba(255,255,255,0.04)),
                rgba(11, 15, 20, 0.45);
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            border: 1px solid var(--glass-border-strong);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--text-primary);
            z-index: 2;
        }

        .project-1 .img {
            background:
                radial-gradient(circle at 60% 30%, rgba(120,90,70,0.55), transparent 55%),
                linear-gradient(160deg, #2a1f17 0%, #120a06 100%);
        }
        .project-2 .img {
            background:
                radial-gradient(circle at 50% 30%, rgba(180, 80, 50, 0.6), transparent 55%),
                linear-gradient(160deg, #2c1612 0%, #0d0606 100%);
        }
        .project-3 .img {
            background:
                radial-gradient(circle at 50% 40%, rgba(53,143,129,0.45), transparent 55%),
                linear-gradient(160deg, #14242a 0%, #060c0e 100%);
        }
        .project-4 .img {
            background:
                radial-gradient(circle at 40% 30%, rgba(70,169,154,0.4), transparent 55%),
                linear-gradient(160deg, #1d1612 0%, #0a0706 100%);
        }

        .project .vendor {
            position: absolute;
            left: 0.95rem;
            top: 0.95rem;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            color: rgba(255,255,255,0.85);
            background:
                linear-gradient(135deg, rgba(255,255,255,0.16), rgba(255,255,255,0.04)),
                rgba(11,15,20,0.4);
            backdrop-filter: blur(18px) saturate(170%);
            -webkit-backdrop-filter: blur(18px) saturate(170%);
            padding: 0.32rem 0.6rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border-strong);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.20);
            z-index: 2;
        }

        .projects-nav {
            position: absolute;
            right: -1.4rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
        }

        /* ===== Testimonial ===== */
        .testimonial {
            position: relative;
            padding: 2.6rem 2.4rem;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.02) 55%, rgba(255,255,255,0.06) 100%),
                rgba(18, 24, 33, 0.40);
            backdrop-filter: blur(34px) saturate(180%);
            -webkit-backdrop-filter: blur(34px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.22),
                inset 0 -1px 0 rgba(255,255,255,0.04),
                0 24px 60px rgba(0,0,0,0.45);
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2.5rem;
            align-items: center;
            overflow: hidden;
        }

        .testimonial::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0) 55%);
            pointer-events: none;
        }

        .testimonial > * { position: relative; z-index: 1; }

        .quote-mark {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 4rem;
            line-height: 0.6;
            color: var(--accent);
            margin-bottom: 0.7rem;
        }

        .testimonial h2 { line-height: 1.05; }

        .testimonial-quote {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.55;
        }

        .testimonial-row {
            display: flex;
            align-items: center;
            gap: 1.1rem;
            margin-top: 1.5rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 0.95rem;
            margin-left: auto;
        }

        .author-pic {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            display: grid;
            place-items: center;
            font-weight: 800;
            color: #fff;
            font-size: 1.1rem;
            border: 2px solid var(--glass-border-strong);
        }

        .author-meta { line-height: 1.3; }
        .author-name { font-weight: 700; font-size: 0.98rem; }
        .author-role { color: var(--text-muted); font-size: 0.84rem; }

        .testimonial-controls {
            display: flex;
            gap: 0.55rem;
        }

        /* ===== Footer ===== */
        footer { padding: 4rem 0 2rem; }

        .footer-grid {
            position: relative;
            display: grid;
            grid-template-columns: 1.4fr 1fr 1.1fr 1.3fr;
            gap: 2.5rem;
            padding: 2.4rem 2.2rem;
            background:
                linear-gradient(135deg, rgba(255,255,255,0.10) 0%, rgba(255,255,255,0.02) 55%, rgba(255,255,255,0.06) 100%),
                rgba(18, 24, 33, 0.38);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.20),
                inset 0 -1px 0 rgba(255,255,255,0.04),
                0 22px 56px rgba(0,0,0,0.4);
            overflow: hidden;
        }

        .footer-grid::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0) 55%);
            pointer-events: none;
        }

        .footer-grid > * { position: relative; z-index: 1; }

        .footer-brand p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.55;
            margin: 1rem 0 1.3rem;
            max-width: 28ch;
        }

        .socials {
            display: flex;
            gap: 0.55rem;
        }

        .socials a {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(255,255,255,0.10), rgba(255,255,255,0.02));
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            color: var(--text-secondary);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.16);
            transition: color 0.2s ease, border-color 0.2s ease, transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }

        .socials a:hover {
            color: var(--accent);
            border-color: var(--accent);
            background: linear-gradient(135deg, rgba(53,143,129,0.30), rgba(53,143,129,0.06));
            transform: translateY(-2px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.22), 0 10px 22px rgba(53,143,129,0.25);
        }

        .footer-col h4 {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 1.1rem;
        }

        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.7rem;
        }

        .footer-col a {
            color: var(--text-secondary);
            font-size: 0.92rem;
            transition: color 0.2s ease;
        }

        .footer-col a:hover { color: var(--accent); }

        .contact-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .contact-list li {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            color: var(--text-secondary);
            font-size: 0.92rem;
        }

        .contact-list svg { color: var(--accent); flex: 0 0 auto; }

        .sub-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.6rem 0.4rem 0;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .sub-footer .legal {
            display: flex;
            gap: 1.4rem;
        }

        .sub-footer .legal a:hover { color: var(--accent); }

        /* ===== Responsive ===== */
        @media (max-width: 1100px) {
            .services-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .projects-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .footer-grid { grid-template-columns: 1.4fr 1fr 1fr; }
            .footer-col.contact { grid-column: 1 / -1; }
            .projects-nav { display: none; }
        }

        @media (max-width: 860px) {
            .nav-links { display: none; position: absolute; top: 70px; left: 1.25rem; right: 1.25rem; flex-direction: column; gap: 0.4rem; padding: 1rem; background: rgba(11,15,20,0.85); backdrop-filter: blur(28px); -webkit-backdrop-filter: blur(28px); border: 1px solid var(--glass-border); border-radius: var(--radius-md); }
            .nav-links.open { display: flex; }
            .nav-links a { padding: 0.7rem 0.4rem; }
            .nav-cta { display: none; }
            .nav-toggle { display: inline-flex; margin-left: auto; }

            .hero-grid { grid-template-columns: 1fr; gap: 2rem; }
            .section-head { grid-template-columns: 1fr; gap: 0.6rem; }
            .head-action { justify-self: start; }

            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem 0; }
            .stat + .stat::before { display: none; }
            .stat { padding: 0.6rem 1rem; }

            .testimonial { grid-template-columns: 1fr; padding: 2rem 1.4rem; gap: 1.4rem; }
            .testimonial-row { flex-direction: column; align-items: flex-start; gap: 1.2rem; }
            .testimonial-author { margin-left: 0; }

            .footer-grid { grid-template-columns: 1fr; gap: 1.8rem; padding: 1.8rem 1.4rem; }
            .footer-col.contact { grid-column: auto; }
            .sub-footer { flex-direction: column; gap: 0.8rem; align-items: flex-start; }
        }

        @media (max-width: 540px) {
            .services-grid { grid-template-columns: 1fr; }
            .projects-grid { grid-template-columns: 1fr; }
            .hero { padding: 2rem 0 3rem; }
            .section { padding: 3rem 0; }
            .nav { padding: 0.55rem 0.55rem 0.55rem 0.85rem; }
            .brand-name { display: none; }
            h1 { font-size: 2.2rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition: none !important;
                animation: none !important;
            }
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body>

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
            <li><a href="index.php" class="is-active" aria-current="page">Home</a></li>
            <li><a href="leistungen.html">Leistungen</a></li>
            <li><a href="projekte.html">Projekte</a></li>
            <li><a href="ueber-uns.html">Über uns</a></li>
            <li><a href="kontakt.html">Kontakt</a></li>
        </ul>

        <a class="btn btn-primary nav-cta" href="kontakt.html">
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

<main id="home">

    <!-- ===== HERO ===== -->
    <section class="hero container">
        <div class="hero-grid">
            <div class="hero-text">
                <span class="eyebrow">Social Media Marketing Agentur</span>
                <h1 class="hero-headline">
                    Wir produzieren<br>
                    keine Videos.<br>
                    Wir liefern <span class="accent-text">Ergebnisse.</span>
                </h1>
                <p class="hero-sub">
                    Strategischer Content, der auffällt. Reels, die performen.
                    Kampagnen, die verkaufen. Für Brands, die mehr wollen.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="kontakt.html">
                        Erstgespräch vereinbaren
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                    <a class="btn btn-ghost" href="leistungen.html">
                        Mehr erfahren
                        <span class="arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                </div>

            </div>

            <div class="hero-visual" aria-hidden="true">
                <span class="scene"></span>
                <span class="desk"></span>
                <span class="monitor"></span>
                <span class="silhouette"></span>

            </div>
        </div>
    </section>

    <!-- ===== LEISTUNGEN ===== -->
    <section class="section container" id="leistungen">
        <div class="section-head">
            <div class="lead">
                <span class="eyebrow">Unsere Leistungen</span>
                <h2>Alles aus einer Hand.</h2>
            </div>
            <p>
                Von der Strategie bis zur Veröffentlichung – wir entwickeln Content,
                der zu deiner Marke passt und deine Zielgruppe erreicht.
            </p>
        </div>

        <div class="services-grid">
            <article class="service-card">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 4 4 5-6"/></svg>
                </div>
                <h3>Strategie</h3>
                <p>Maßgeschneiderte Strategien für nachhaltiges Wachstum und maximale Reichweite.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </article>

            <article class="service-card">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 6l2-3"/><path d="M13 6l-2-3"/><path d="M17 6l2-3"/></svg>
                </div>
                <h3>Content Creation</h3>
                <p>Reels, Videos &amp; Fotos, die auffallen, begeistern und zum Handeln bewegen.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </article>

            <article class="service-card">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10"/><path d="M10 20V4"/><path d="M16 20v-7"/><path d="M22 20H2"/></svg>
                </div>
                <h3>Social Media Management</h3>
                <p>Wir übernehmen Plattform, Community &amp; Content – damit du dich um nichts kümmern musst.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </article>

            <article class="service-card">
                <div class="service-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22 22 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg>
                </div>
                <h3>Performance Marketing</h3>
                <p>Gezielte Kampagnen, die nicht nur Reichweite bringen, sondern Ergebnisse liefern.</p>
                <span class="service-arrow" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </article>
        </div>

        <!-- Stats -->
        <div class="stats" aria-label="Unsere Zahlen">
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                </span>
                <div>
                    <div class="stat-num">30+</div>
                    <div class="stat-label">Projekte<br>abgeschlossen</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                </span>
                <div>
                    <div class="stat-num">15+</div>
                    <div class="stat-label">zufriedene<br>Kunden</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </span>
                <div>
                    <div class="stat-num">2.5M+</div>
                    <div class="stat-label">erreichte<br>Konten</div>
                </div>
            </div>
            <div class="stat">
                <span class="stat-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
                <div>
                    <div class="stat-num">98%</div>
                    <div class="stat-label">Kunden<br>zufriedenheit</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PROJEKTE ===== -->
    <section class="section container" id="projekte">
        <div class="section-head">
            <div class="lead">
                <span class="eyebrow">Ausgewählte Projekte</span>
                <h2>Echte Ergebnisse. Echte Brands.</h2>
            </div>
            <a class="ghost-link head-action" href="projekte.html">
                Alle Projekte ansehen
                <span aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>

        <div class="projects-grid">
            <article class="project project-1">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Social Media &amp; Reels</span>
            </article>
            <article class="project project-2">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Event Content</span>
            </article>
            <article class="project project-3">
                <div class="img" aria-hidden="true"></div>
                <span class="label">Video Production</span>
            </article>
            <article class="project project-4">
                <div class="img" aria-hidden="true"></div>
                <span class="vendor">VB</span>
                <span class="label">Social Media &amp; Ads</span>
            </article>

            <button class="icon-btn projects-nav" type="button" aria-label="Weitere Projekte">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
            </button>
        </div>
    </section>

    <!-- ===== TESTIMONIAL ===== -->
    <section class="section container" id="ueber-uns">
        <div class="testimonial">
            <div>
                <div class="quote-mark" aria-hidden="true">&ldquo;</div>
                <h2>Das sagen<br>unsere Kunden.</h2>
            </div>
            <div>
                <p class="testimonial-quote">
                    „Die Zusammenarbeit mit Dodidis.Media hat unseren Social Media Auftritt
                    auf ein neues Level gebracht. Kreativ, zuverlässig und immer einen
                    Schritt voraus."
                </p>
                <div class="testimonial-row">
                    <div class="testimonial-author">
                        <div class="author-pic" aria-hidden="true">M</div>
                        <div class="author-meta">
                            <div class="author-name">Marvin Becker</div>
                            <div class="author-role">Gründer von Becker Fitness</div>
                        </div>
                    </div>
                    <div class="testimonial-controls">
                        <button class="icon-btn" type="button" aria-label="Vorheriges Testimonial">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M11 19l-7-7 7-7"/></svg>
                        </button>
                        <button class="icon-btn" type="button" aria-label="Nächstes Testimonial">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- ===== FOOTER ===== -->
<footer id="kontakt">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="#home">
                    <span class="brand-mark" aria-hidden="true">DM</span>
                    <span class="brand-name">DODIDIS.MEDIA</span>
                </a>
                <p>
                    Wir sind die junge Social Media Marketing Agentur aus Nordhessen
                    und helfen Brands, online sichtbar zu werden und zu wachsen.
                </p>
                <div class="socials" aria-label="Social Media">
                    <a href="#" aria-label="Instagram">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="#" aria-label="TikTok">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5.8 20.1a6.34 6.34 0 0 0 10.86-4.43V8.36a8.16 8.16 0 0 0 4.77 1.52V6.43a4.85 4.85 0 0 1-1.84-.31z"/></svg>
                    </a>
                    <a href="#" aria-label="YouTube">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/></svg>
                    </a>
                    <a href="#" aria-label="LinkedIn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Navigation</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="leistungen.html">Leistungen</a></li>
                    <li><a href="projekte.html">Projekte</a></li>
                    <li><a href="ueber-uns.html">Über uns</a></li>
                    <li><a href="kontakt.html">Kontakt</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Leistungen</h4>
                <ul>
                    <li><a href="leistungen.html">Strategie</a></li>
                    <li><a href="leistungen.html">Content Creation</a></li>
                    <li><a href="leistungen.html">Social Media Management</a></li>
                    <li><a href="leistungen.html">Performance Marketing</a></li>
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
            <span>© <?= $year ?> Dodidis.Media – Alle Rechte vorbehalten</span>
            <div class="legal">
                <a href="#">Impressum</a>
                <a href="#">Datenschutz</a>
            </div>
        </div>
    </div>
</footer>

<script>
    (function () {
        const toggle = document.getElementById('navToggle');
        const links  = document.getElementById('navLinks');
        if (!toggle || !links) return;

        toggle.addEventListener('click', function () {
            const isOpen = links.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        links.addEventListener('click', function (e) {
            if (e.target.tagName === 'A') {
                links.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    })();
</script>

</body>
</html>
