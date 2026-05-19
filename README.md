# Dodidis Media Event – PHP + Node.js Sidecar (Plesk)

Dieses Repository bleibt **primär eine PHP/MySQL-Website**. Node.js wird nur ergänzend als Sidecar betrieben.

## Ziel
- Bestehende PHP-Seiten in `/httpdocs` bleiben unverändert.
- Node.js läuft separat auf einem eigenen Port (z. B. 3000) und wird über Plesk Node.js verwaltet.
- Deployment bleibt GitHub-kompatibel.

## Enthaltene Node-Dateien
- `app.js` – Minimaler Express-Testserver.
- `package.json` – npm Metadaten, Start-Skripte und Abhängigkeiten.
- `.gitignore` – `node_modules` und lokale Artefakte ausgeschlossen.
- `ecosystem.config.js` – optionale PM2-Konfiguration (nur falls benötigt).

## Lokale/SSH Installation
```bash
npm install
npm run dev
npm start
```

## Plesk Startkonfiguration (Node.js Erweiterung)
1. **Domain**: `dodidis-media-event.de`
2. **Document Root**: bleibt `/httpdocs` (PHP Hauptsystem)
3. **Node.js aktivieren** auf derselben Domain oder Subdomain
4. **Application Root**: Ordner mit `app.js` und `package.json` (typisch `/httpdocs`)
5. **Application Startup File**: `app.js`
6. **Application Mode**: `production`
7. **Environment Variables**:
   - `NODE_ENV=production`
   - `PORT=3000` (oder Plesk-Standard-Port, falls gesetzt)
8. In Plesk: **NPM installieren** (`npm install`) und danach **Restart App**

> Wichtig: Kein Apache/PHP-Routing anfassen. Node.js bleibt ergänzend und getrennt.

## GitHub Deploy Workflow (shared-hosting-tauglich)
### Einmalig auf Server
```bash
cd /var/www/vhosts/<deine-domain>/httpdocs
git clone <repo-url> .
npm install --omit=dev
```

### Update-Deploy
```bash
cd /var/www/vhosts/<deine-domain>/httpdocs
git pull origin main
npm install --omit=dev
```

Danach in Plesk: **Restart App**.

## Codex Remote / SSH Vorbereitung
Auf dem Server prüfen:
```bash
which node
node -v
which npm
npm -v
echo $PATH
```

Wenn `node`/`npm` via SSH nicht gefunden werden, in `~/.bashrc` den Plesk/Node-Pfad ergänzen und Shell neu laden:
```bash
source ~/.bashrc
```

## Sicherheits-/Betriebshinweise
- Keine bestehenden PHP-Dateien überschreiben.
- `node_modules` nicht ins Repo committen.
- Secrets nur über `.env`/Plesk Environment Variables.
- Für Produktionsbetrieb optional Healthcheck über `/` testen.
