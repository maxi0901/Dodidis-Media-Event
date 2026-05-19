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
  <link rel="icon" href="DM_Logo_4K_ohneschrift_invert.png">
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
    .login-logo img { width: 52px; height: 52px; object-fit: contain; }
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
  </style>
</head>
<body>
<div class="login-screen">
  <div class="login-box">
    <div class="login-logo"><img src="DM_Logo_4K_ohneschrift_invert.png" alt="Dodidis Media"></div>
    <div class="login-title">Dodidis Media</div>
    <div class="login-sub">Internes Management Tool</div>

    <div class="login-tabs">
      <button class="login-tab active" data-tab="staff"    type="button" onclick="switchTab('staff')">Mitarbeiter</button>
      <button class="login-tab"        data-tab="customer" type="button" onclick="switchTab('customer')">Kunde</button>
    </div>

    <div id="login-error" class="login-error hide"></div>

    <form id="staff-login" onsubmit="loginStaff(event)" autocomplete="on">
      <label for="login-username">Benutzername</label>
      <input type="text" id="login-username" autocomplete="username" required>
      <label for="login-password">Passwort</label>
      <input type="password" id="login-password" autocomplete="current-password" required>
      <button class="btn-primary" type="submit">Anmelden</button>
    </form>

    <form id="customer-login" class="hide" onsubmit="loginCustomer(event)" autocomplete="on">
      <label for="login-customer-number">Kundennummer</label>
      <input type="text" id="login-customer-number" required>
      <label for="login-customer-pin">PIN</label>
      <input type="password" id="login-customer-pin" autocomplete="off" inputmode="numeric" required>
      <button class="btn-primary" type="submit">Anmelden</button>
      <div class="login-hint">Erst-Login: PIN = Kundennummer</div>
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
  let j;
  try { j = await r.json(); } catch (_) { j = { success: false, error: 'Ungültige Antwort (HTTP ' + r.status + ')' }; }
  return { ok: r.ok && j.success, data: j.data, error: j.error };
}

async function loginStaff(e) {
  e.preventDefault();
  hideError();
  const username = document.getElementById('login-username').value.trim();
  const password = document.getElementById('login-password').value;
  const hash = await sha256(password);
  const res = await postJson('login', { type: 'staff', username, password_hash: hash });
  if (!res.ok) { showError(res.error || 'Anmeldung fehlgeschlagen.'); return; }
  location.href = 'index.php';
}

async function loginCustomer(e) {
  e.preventDefault();
  hideError();
  const num = document.getElementById('login-customer-number').value.trim();
  const pin = document.getElementById('login-customer-pin').value;
  const pinHash = await sha256(pin || num);
  const res = await postJson('login', { type: 'customer', customer_number: num, pin_hash: pinHash });
  if (!res.ok) { showError(res.error || 'Anmeldung fehlgeschlagen.'); return; }
  location.href = 'index.php';
}
</script>
</body>
</html>
