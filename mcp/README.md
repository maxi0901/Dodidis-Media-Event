# Dodidis-Media MCP-Server

MCP-Server, mit dem Claude direkt im Agenturtool arbeiten kann — er handelt über
einen **persönlichen API-Token** als der jeweilige Nutzer (mit dessen
Rollen/Rechten, protokolliert unter dessen ID).

Deckt ab: Projekte/Videos **eintragen & ändern**, **Status** setzen, Termine
(über das Fallback-Tool: `meetings.php`, `vacations.php`, …), **Posting**
planen/direkt posten, sowie **Lesen** zum Beantworten von Fragen.
**Nicht** enthalten: das Hochladen der Video-Datei selbst (bleibt Browser→NAS).

## 1. Token erzeugen
Im Tool: **Profil → „API-Token" → „Neuen Token erzeugen"**. Der Klartext wird
**nur einmal** angezeigt — sicher speichern.

## 2. Installieren & bauen
Voraussetzung: Node.js ≥ 18 (getestet mit v22).

```bash
cd mcp
npm install
npm run build
```

## 3. In Claude eintragen (lokal, stdio)
**Claude Desktop** — `claude_desktop_config.json`:
```json
{
  "mcpServers": {
    "dodidis-media": {
      "command": "node",
      "args": ["/ABSOLUTER/PFAD/zu/mcp/dist/index.js"],
      "env": {
        "DODIDIS_API_BASE": "https://dodidis-media.de/agenturtool/api",
        "DODIDIS_API_TOKEN": "dm_dein_token_hier"
      }
    }
  }
}
```

**Claude Code** — im Projekt:
```bash
claude mcp add dodidis-media \
  --env DODIDIS_API_BASE=https://dodidis-media.de/agenturtool/api \
  --env DODIDIS_API_TOKEN=dm_dein_token_hier \
  -- node /ABSOLUTER/PFAD/zu/mcp/dist/index.js
```

Claude danach neu starten. Die Tools erscheinen unter „dodidis-media".

## 4. Remote-Betrieb (vServer) — für den claude.ai/Desktop *Connector*
Damit du den Server über **„Benutzerdefinierten Connector hinzufügen → Remote MCP
Server URL"** (auch mobil/claude.ai) nutzen kannst, läuft er als HTTP-Dienst auf
dem vServer. Absicherung: ein **geheimes URL-Segment** (`MCP_URL_SECRET`) — das
Connector-Fenster erlaubt keine eigenen Header, daher ist die unratbare URL der
Schlüssel (analog zu den Kalender-Abo-Links), geschützt über HTTPS.

### a) Secret erzeugen
```bash
openssl rand -hex 24
```

### b) Auf dem vServer bereitstellen
```bash
# Repo (nur den mcp-Ordner) auf den vServer bringen, dann:
cd /opt/dodidis-mcp        # z. B.
npm install
npm run build
```

### c) systemd-Service `/etc/systemd/system/dodidis-mcp.service`
```ini
[Unit]
Description=Dodidis MCP (HTTP)
After=network.target

[Service]
WorkingDirectory=/opt/dodidis-mcp
ExecStart=/usr/bin/node dist/http.js
Environment=PORT=8787
Environment=DODIDIS_API_BASE=https://dodidis-media.de/agenturtool/api
Environment=DODIDIS_API_TOKEN=dm_DEIN_TOKEN
Environment=MCP_URL_SECRET=DEIN_OPENSSL_SECRET
Restart=always
User=www-data

[Install]
WantedBy=multi-user.target
```
```bash
sudo systemctl daemon-reload && sudo systemctl enable --now dodidis-mcp
sudo systemctl status dodidis-mcp   # sollte "active (running)" sein
```

### d) Caddy-Route (eigene Subdomain, TLS automatisch)
Im Caddyfile ergänzen:
```caddy
mcp.dodidis-media.de {
    reverse_proxy 127.0.0.1:8787
}
```
Caddy neu laden. DNS: `mcp.dodidis-media.de` auf den vServer zeigen lassen.

### e) In claude.ai eintragen
**Benutzerdefinierten Connector hinzufügen** → *Remote MCP Server URL*:
```
https://mcp.dodidis-media.de/mcp/DEIN_OPENSSL_SECRET
```
OAuth-Felder **leer** lassen. Fertig — die Tools erscheinen als Connector.

> Der HTTP-Server nutzt den in der Service-Env hinterlegten `DODIDIS_API_TOKEN` —
> handelt also als **dieser** Nutzer. Für einen anderen Nutzer einen eigenen
> Dienst mit dessen Token + eigenem Secret betreiben.

## Tools
**Lesen:** `projekte_auflisten`, `projekt_lesen`, `kunden_auflisten`,
`mitarbeiter_auflisten`, `drehtage_auflisten`, `posting_kalender_lesen`,
`posting_pool_lesen`, `diagnose_lesen`.

**Schreiben:** `projekt_anlegen`, `projekt_aendern`, `projekt_status_setzen`,
`posting_planen`, `posting_jetzt`.

**Fallback:** `tool_api_call` (method, path, query?, body?) — erreicht jeden
erlaubten Endpunkt (z. B. `meetings.php`, `todos.php`, `vacations.php`,
`shootdays.php`, `customers.php`). Damit ist „alles, was der Account im Tool
darf" abgedeckt, auch ohne eigenes Tool.

## Sicherheit
- Der Token trägt **nur** die Rechte des Nutzers. Ein Videograf-Token kann nur
  Videograf-Dinge, ein Admin-Token alles.
- Token jederzeit widerrufbar (Profil → API-Token → „Widerrufen").
- Heikle Aktionen (`posting_jetzt`, Löschen, Freigeben) vorher bestätigen lassen.
- `.env`/Token **niemals committen** (siehe `.gitignore`).

## Beispiel
> „Leg ein Video ‚Reel Mai' für Kunde *test* an, Videograf *Timo Block*, Cutter
> *Test tester*, Posting am 20.05." → Claude ruft `kunden_auflisten` +
> `mitarbeiter_auflisten` (Namen→IDs) und dann `projekt_anlegen` auf.
