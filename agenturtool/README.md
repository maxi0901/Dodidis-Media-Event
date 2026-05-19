# Dodidis Media – Agenturtool

Internes Management-Tool. Frontend ist eine Single-Page-App in `index.html`; das Backend besteht aus normalisierten MySQL-Tabellen, einer auth-geschützten Snapshot-API (`api.php`) und zusätzlichen REST-Endpoints unter `api/`.

## Architektur

```
agenturtool/
├── index.php              Session-Gate – serviert index.html nur an Eingeloggte
├── login.php              Login-Form (Mitarbeiter + Kunde)
├── logout.php
├── index.html             SPA (UI/CSS unverändert)
├── config.php             DB-Credentials + Session-/Upload-Settings
├── api.php                Legacy-Snapshot-API (auth-geschützt, mappt auf normalisiertes Schema)
├── db-test.php            Sanity-Check (Browser-Endpoint)
├── install.sql            Vollständiges DDL
├── seed_users.sql         Bestands-User mit Original-Hashes + Rollen
├── migrate_legacy.php     Einmaliger Importer aus agentur-backup-…json
├── api/
│   ├── auth.php           Login/Logout/Me (Sessions, CSRF)
│   ├── users.php          CRUD (admin/manager/self)
│   ├── customers.php      CRUD (admin/manager + customer-self-read)
│   ├── projects.php       CRUD + Status-Workflow
│   ├── todos.php          CRUD + assignees + seen_by
│   ├── vacations.php      CRUD (self/admin)
│   ├── shootdays.php      CRUD (admin/manager/videograf)
│   ├── upload.php         Multipart-Upload mit MIME-Whitelist
│   └── dashboard.php      Light-Aggregat (Polling) + ?initial=1 Bootstrap
├── includes/
│   ├── db.php             PDO-Singleton (utf8mb4, EMULATE_PREPARES=false)
│   ├── auth-check.php     Session-Management, Rollen, CSRF
│   ├── helpers.php        input_json, uid, as_date, log_activity …
│   └── response.php       json_ok / json_err
└── uploads/
    ├── .htaccess          PHP-Execution deaktiviert
    ├── projects/<id>/
    └── avatars/<id>/
```

## Installation

1. **Schema anlegen** – `install.sql` in phpMyAdmin auf `k275333_dodidis-Media` ausführen.
2. **User-Seed** – `seed_users.sql` ausführen → 6 Bestands-User mit bestehenden `sha256$`-Hashes.
3. **Konfiguration prüfen** – `config.php` öffnen, ggf. `cookie_secure` an HTTPS-Setup anpassen.
4. **Bestandsdaten importieren** – `php migrate_legacy.php` (CLI) oder über `?token=…` (Token in `config.php` setzen).
5. **Sanity-Check** – Browser: `https://…/agenturtool/db-test.php` → JSON mit Row-Counts ≥ 1.
6. **App starten** – `https://…/agenturtool/` → wird automatisch zu `login.php` umgeleitet.

## Login

Mitarbeiter: Username + Passwort (Hashes aus dem Backup bleiben gültig).
Kunde: Kundennummer + PIN. Erstlogin: PIN = Kundennummer.

## REST-API

Alle Endpoints unter `/agenturtool/api/`:

| Endpoint              | GET (list/one)                                 | POST          | PUT                                            | DELETE        |
|-----------------------|------------------------------------------------|---------------|------------------------------------------------|---------------|
| `auth.php`            | `?action=me`                                   | `?action=login`/`logout` | –                                       | –             |
| `users.php`           | admin/manager + self                           | admin         | self / admin                                   | admin         |
| `customers.php`       | admin/manager + customer-self                  | admin/manager | admin/manager                                  | admin         |
| `projects.php`        | admin/manager / videograf+cutter own / customer own | admin/manager | admin/manager; videograf/cutter nur `status`   | admin/manager |
| `todos.php`           | admin/manager / assignee                       | admin/manager | admin/manager; assignee nur `status`+`seenBy`  | admin/manager |
| `vacations.php`       | alle (Transparenz)                             | self/admin    | self/admin                                     | self/admin    |
| `shootdays.php`       | admin/manager/videograf                        | admin/manager | admin/manager                                  | admin/manager |
| `upload.php`          | –                                              | admin/manager | –                                              | –             |
| `dashboard.php`       | jede eingeloggte Session                       | –             | –                                              | –             |

Antwortformat: `{"success":true,"data":…}` / `{"success":false,"error":"…"}`.

Alle nicht-GET-Requests benötigen den Header `X-CSRF-Token` (vom Backend bei Login in der Session bereitgestellt, im Frontend über die `meta[name=csrf]`-Marke geladen).

## Polling

Frontend pollt alle 12 s `GET /api/dashboard.php` (mit `If-None-Match`-Header). Bei `last_change`-Diff oder Status 200 wird `api.php?action=pull` ausgelöst und der Store rerendert.

## Sicherheit

- PDO Prepared Statements überall
- Session: `HttpOnly`, `SameSite=Lax`, `Secure` (HTTPS), Sliding-Expiry 14 Tage, Regeneration nach Login
- CSRF-Token-Validierung bei POST/PUT/DELETE
- `password_hash` und `pin_hash` werden NIE im JSON ausgeliefert
- Upload-MIME-Whitelist via `finfo` + `.htaccess` deaktiviert PHP-Execution in `uploads/`
- `.htaccess` blockt Direktzugriff auf `config.php`, `install.sql`, `migrate_legacy.php`, JSON-Backups

## Verifikation (curl)

```bash
# 1. DB-Test
curl -s https://…/agenturtool/db-test.php | jq

# 2. Login (Hash vom Frontend wird hier manuell gebildet, hier z.B. via openssl):
HASH="sha256$$(printf 'mein-passwort' | openssl dgst -sha256 -hex | awk '{print $2}')"
curl -i -c cookies.txt -X POST \
  -H 'Content-Type: application/json' \
  -d "{\"type\":\"staff\",\"username\":\"Raphael\",\"password_hash\":\"$HASH\"}" \
  https://…/agenturtool/api/auth.php?action=login

# 3. Me
curl -s -b cookies.txt https://…/agenturtool/api/auth.php?action=me | jq

# 4. Dashboard
curl -s -b cookies.txt https://…/agenturtool/api/dashboard.php?initial=1 | jq

# 5. Pull (Legacy)
curl -s -b cookies.txt https://…/agenturtool/api.php?action=pull | jq '.data | keys'
```

## Cleanup nach erfolgreicher Migration

- Backup `agentur-backup-2026-05-19.json` aus dem Webroot entfernen (oder durch `.htaccess` geschützt belassen — bereits konfiguriert).
- `Fehler.log` löschen / archivieren.
- Wenn die Per-Entity-REST-API komplett im Frontend genutzt wird, kann `api.php` entfernt werden. Aktuell läuft die SPA aus Kompatibilitätsgründen weiterhin über `api.php` (Bulk-Snapshot mit Auth + Mapping).
