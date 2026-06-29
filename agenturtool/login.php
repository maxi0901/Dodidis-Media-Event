<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth-check.php';
start_app_session();

// Bereits eingeloggt? → direkt zur App
if (!empty($_SESSION['uid']) || !empty($_SESSION['cid'])) {
    header('Location: index.php');
    exit;
}

// Pfad-Helper: relativ zum Webroot funktioniert sowohl im Unterordner als auch direkt
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
?><!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Login – Dodidis Media</title>
  <link rel="icon" href="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>DM_Logo_4K_ohneschrift_invert.png">
  <style>
    :root {
      --bg-0: #000; --bg-1: #0d0f12; --bg-2: #151820; --bg-3: #1c2029;
      --border: rgba(255,255,255,0.08);
      --accent: #4aa198; --accent-hover: #5fb5ac;
      --text-1: #f5f5f7; --text-2: #a1a1aa; --text-3: #6b6b75;
      --danger: #ef4444;
    }
    * { box-sizing: border-box; }
    html, body { background: #000; color: var(--text-1); margin: 0; height: 100%;
                 font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Segoe UI', Roboto, sans-serif; }
    .login-screen {
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      background: radial-gradient(1200px 600px at 20% 10%, rgba(74,161,152,.15), transparent 60%),
                  radial-gradient(900px 500px at 90% 90%, rgba(74,161,152,.10), transparent 65%),
                  #000;
      padding: 24px;
    }
    .login-box {
      width: 100%; max-width: 380px; background: rgba(21,24,32,.85);
      border: 1px solid var(--border); border-radius: 22px; padding: 40px 32px;
      backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
      box-shadow: 0 30px 80px -20px rgba(0,0,0,.7);
    }
    .login-logo { width: 72px; height: 72px; border-radius: 16px; background: #000;
      display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; overflow: hidden; }
    .login-logo img { width: 52px; height: 52px; object-fit: contain; filter: brightness(0) invert(1); opacity: .95; }
    .logo-fallback { color: #fff; font-weight: 700; letter-spacing: .06em; font-size: 24px; }
    .login-title { font-size: 22px; font-weight: 600; text-align: center; margin: 0 0 4px; }
    .login-sub   { font-size: 13px; color: var(--text-2); text-align: center; margin-bottom: 22px; }
    .login-tabs  { display: flex; gap: 8px; margin-bottom: 18px; background: var(--bg-3);
      padding: 4px; border-radius: 12px; }
    .login-tab   { flex: 1; padding: 8px 12px; font-size: 13px; border: 0; background: transparent;
      color: var(--text-2); border-radius: 8px; cursor: pointer; }
    .login-tab.active { background: var(--bg-1); color: var(--text-1); }
    .login-error { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3);
      color: var(--danger); padding: 10px 12px; border-radius: 10px; font-size: 13px; margin-bottom: 14px; }
    .hide { display: none !important; }
    label { font-size: 12px; color: var(--text-2); display: block; margin: 10px 0 4px; }
    input[type=text], input[type=password] {
      width: 100%; padding: 10px 12px; background: var(--bg-1);
      border: 1px solid var(--border); border-radius: 10px; color: var(--text-1);
      font-size: 14px; outline: none;
    }
    input:focus { border-color: var(--accent); }
    .btn-primary { width: 100%; margin-top: 16px; padding: 11px 14px; border: 0;
      border-radius: 10px; background: var(--accent); color: #fff; font-weight: 500;
      font-size: 14px; cursor: pointer; }
    .btn-primary:hover { background: var(--accent-hover); }
    .login-hint { font-size: 12px; color: var(--text-3); margin-top: 12px; text-align: center; }
    .remember-row { display: flex; align-items: center; gap: 8px; margin-top: 12px; cursor: pointer; user-select: none; }
    .remember-row input[type=checkbox] { display: none; }
    .remember-toggle {
      width: 38px; height: 22px; border-radius: 11px; background: var(--bg-3);
      border: 1px solid var(--border); flex-shrink: 0; position: relative;
      transition: background .2s;
    }
    .remember-toggle::after {
      content: ''; position: absolute; top: 3px; left: 3px;
      width: 14px; height: 14px; border-radius: 50%;
      background: var(--text-3); transition: transform .2s, background .2s;
    }
    .remember-row input:checked + .remember-toggle { background: var(--accent); border-color: var(--accent); }
    .remember-row input:checked + .remember-toggle::after { transform: translateX(16px); background: #fff; }
    .remember-label { font-size: 13px; color: var(--text-2); }
    .login-step-title { font-size: 18px; font-weight: 600; margin: 0 0 6px; }
    .login-step-sub   { font-size: 13px; color: var(--text-2); margin-bottom: 20px; }
    .setup-error { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3);
      color: var(--danger); padding: 10px 12px; border-radius: 10px; font-size: 13px; margin-bottom: 14px; }

    /* Mobile-Optimierung */
    @supports (height: 100dvh) {
      .login-screen { min-height: 100dvh; }
    }
    .login-screen {
      padding-top: calc(24px + env(safe-area-inset-top, 0px));
      padding-bottom: calc(24px + env(safe-area-inset-bottom, 0px));
      padding-left: calc(24px + env(safe-area-inset-left, 0px));
      padding-right: calc(24px + env(safe-area-inset-right, 0px));
    }
    @media (max-width: 480px) {
      .login-box { padding: 28px 20px; border-radius: 18px; }
      .login-logo { width: 64px; height: 64px; }
      .login-logo img { width: 44px; height: 44px; }
      .login-title { font-size: 19px; }
      .login-sub { font-size: 12px; }
      .login-tab { min-height: 44px; padding: 10px 12px; font-size: 13px; }
      /* iOS-Auto-Zoom-Fix: Inputs >= 16px */
      input[type=text], input[type=password] {
        font-size: 16px; padding: 12px 14px; min-height: 44px;
      }
      .btn-primary { min-height: 46px; font-size: 15px; padding: 12px 14px; }
      label { font-size: 12.5px; }
    }
    @media (max-height: 480px) and (orientation: landscape) {
      .login-screen { align-items: flex-start; }
      .login-box { margin-top: 12px; padding: 20px 22px; }
      .login-logo { width: 48px; height: 48px; margin-bottom: 10px; }
      .login-logo img { width: 34px; height: 34px; }
      .login-title { font-size: 18px; }
      .login-tabs { margin-bottom: 12px; }
    }
    * { -webkit-tap-highlight-color: transparent; }
    button, .login-tab, .btn-primary { touch-action: manipulation; }
  </style>
</head>
<body>
<div class="login-screen">
  <div class="login-box">
    <div class="login-logo" aria-label="Dodidis Media Logo">
      <img id="dm-logo" src="<?= htmlspecialchars($basePath, ENT_QUOTES) ?>DM_Logo_4K_ohneschrift_invert.png" alt="Dodidis Media"
           onerror="this.classList.add('hide'); document.getElementById('dm-logo-fallback').classList.remove('hide');">
      <span id="dm-logo-fallback" class="logo-fallback hide" aria-hidden="true">DM</span>
    </div>
    <div class="login-title">Dodidis Media</div>
    <div class="login-sub">Internes Management Tool</div>

    <div class="login-tabs">
      <button class="login-tab active" data-tab="staff"    type="button" onclick="switchTab('staff')">Mitarbeiter</button>
      <button class="login-tab"        data-tab="customer" type="button" onclick="switchTab('customer')">Kunde</button>
    </div>

    <div id="login-error" class="login-error hide"></div>

    <div id="login-form-area">
      <form id="staff-login" onsubmit="loginStaff(event)" autocomplete="on">
        <label for="login-username">Benutzername</label>
        <input type="text" id="login-username" autocomplete="username" required>
        <label for="login-password">Passwort</label>
        <input type="password" id="login-password" autocomplete="current-password">
        <div class="login-hint" style="text-align:left;margin:6px 0 0">Erstmalig? Passwortfeld leer lassen.</div>
        <label class="remember-row" for="staff-remember">
          <input type="checkbox" id="staff-remember" checked>
          <span class="remember-toggle"></span>
          <span class="remember-label">Angemeldet bleiben</span>
        </label>
        <button class="btn-primary" type="submit">Anmelden</button>
      </form>
    </div>

    <div id="password-setup" class="hide">
      <p class="login-step-title">Passwort festlegen</p>
      <p class="login-step-sub">Wähle ein Passwort für deine zukünftigen Logins.</p>
      <div id="setup-error" class="setup-error hide"></div>
      <form id="setup-form" onsubmit="setPassword(event)" autocomplete="off">
        <label for="setup-pw1">Neues Passwort</label>
        <input type="password" id="setup-pw1" autocomplete="new-password" minlength="6" required>
        <label for="setup-pw2">Passwort bestätigen</label>
        <input type="password" id="setup-pw2" autocomplete="new-password" minlength="6" required>
        <button class="btn-primary" type="submit">Passwort speichern &amp; starten</button>
      </form>
    </div>

    <form id="customer-login" class="hide" onsubmit="loginCustomer(event)" autocomplete="on">
      <label for="login-customer-number">Kundennummer</label>
      <input type="text" id="login-customer-number" required>
      <label for="login-customer-pin">PIN</label>
      <input type="password" id="login-customer-pin" autocomplete="off" inputmode="numeric" required>
      <div class="login-hint" style="margin-top:6px;">Erst-Login: PIN = Kundennummer</div>
      <label class="remember-row" for="customer-remember">
        <input type="checkbox" id="customer-remember" checked>
        <span class="remember-toggle"></span>
        <span class="remember-label">Angemeldet bleiben</span>
      </label>
      <button class="btn-primary" type="submit">Anmelden</button>
    </form>
  </div>
</div>

<script>
const API_BASE = <?= json_encode($basePath . 'api', JSON_UNESCAPED_SLASHES) ?>;

function switchTab(tab) {
  document.querySelectorAll('.login-tab').forEach(el =>
    el.classList.toggle('active', el.dataset.tab === tab));
  document.getElementById('staff-login').classList.toggle('hide',    tab !== 'staff');
  document.getElementById('customer-login').classList.toggle('hide', tab !== 'customer');
  hideError();
}
function showError(m) {
  const el = document.getElementById('login-error');
  el.textContent = m; el.classList.remove('hide');
}
function hideError() { document.getElementById('login-error').classList.add('hide'); }

async function sha256(plain) {
  if (!plain) return '';
  if (plain.startsWith('sha256$')) return plain;
  const buf = new TextEncoder().encode(plain);
  const digest = await crypto.subtle.digest('SHA-256', buf);
  const hex = Array.from(new Uint8Array(digest)).map(b => b.toString(16).padStart(2, '0')).join('');
  return 'sha256$' + hex;
}

async function postJson(action, body) {
  const r = await fetch(API_BASE + '/auth.php?action=' + action, {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  });

  const raw = await r.text();
  let j = null;
  try {
    j = raw ? JSON.parse(raw) : null;
  } catch (_) {
    const serverHint = raw
      ? raw.replace(/\s+/g, ' ').trim().slice(0, 140)
      : 'Leere Serverantwort';
    return {
      ok: false,
      data: null,
      error: `Serverantwort ist kein JSON (HTTP ${r.status}). ${serverHint}`
    };
  }

  if (!j || typeof j !== 'object') {
    return { ok: false, data: null, error: `Ungültiges Antwortformat (HTTP ${r.status}).` };
  }

  return {
    ok: Boolean(r.ok && (j.success || j.ok)),
    data: j.data ?? null,
    error: j.error || j.message || (r.ok ? 'Anmeldung fehlgeschlagen.' : `HTTP ${r.status}`)
  };
}

let _csrf = '';

async function loginStaff(e) {
  e.preventDefault();
  hideError();
  const username = document.getElementById('login-username').value.trim();
  const password = document.getElementById('login-password').value;
  const hash     = password ? await sha256(password) : '';
  const remember = document.getElementById('staff-remember').checked;
  const res = await postJson('login', { type: 'staff', username, password_hash: hash, remember });
  if (!res.ok) { showError(res.error || 'Anmeldung fehlgeschlagen.'); return; }
  _csrf = res.data?.csrf || '';
  if (res.data?.must_set_password) {
    document.getElementById('login-form-area').classList.add('hide');
    document.getElementById('login-error').classList.add('hide');
    document.querySelectorAll('.login-tabs').forEach(el => el.classList.add('hide'));
    document.getElementById('password-setup').classList.remove('hide');
    document.getElementById('setup-pw1').focus();
    return;
  }
  location.href = 'index.php';
}

function showSetupError(m) {
  const el = document.getElementById('setup-error');
  el.textContent = m; el.classList.remove('hide');
}

async function setPassword(e) {
  e.preventDefault();
  document.getElementById('setup-error').classList.add('hide');
  const pw1 = document.getElementById('setup-pw1').value;
  const pw2 = document.getElementById('setup-pw2').value;
  if (pw1.length < 6)  { showSetupError('Mindestens 6 Zeichen erforderlich.'); return; }
  if (pw1 !== pw2)     { showSetupError('Passwörter stimmen nicht überein.'); return; }
  const hash = await sha256(pw1);
  const res  = await postJson('set_password', { password_hash: hash });
  if (!res.ok) { showSetupError(res.error || 'Fehler beim Speichern.'); return; }
  location.href = 'index.php';
}

async function loginCustomer(e) {
  e.preventDefault();
  hideError();
  const num = document.getElementById('login-customer-number').value.trim();
  const pin = document.getElementById('login-customer-pin').value;
  const pinHash = await sha256(pin || num);
  const remember = document.getElementById('customer-remember').checked;
  const res = await postJson('login', { type: 'customer', customer_number: num, pin_hash: pinHash, remember });
  if (!res.ok) { showError(res.error || 'Anmeldung fehlgeschlagen.'); return; }
  location.href = 'index.php';
}
</script>
</body>
</html>
