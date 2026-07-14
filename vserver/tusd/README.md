# Resumable Upload — vServer-Setup (tusd + NAS-Push)

Schneller, wiederaufnehmbarer Upload großer Rohdateien direkt zum **vServer**;
danach schiebt ein Hook die Datei per WebDAV auf den **NAS** und löscht den
Puffer erst, wenn sie dort vollständig liegt.

```
Browser (tus, resumable)
   → Caddy  https://nas.dodidis-media.de/files/  (vServer)
   → tusd   puffert nach /var/lib/tusd-data       (vServer)
   → post-finish-Hook → nas-push.sh
       → WebDAV-PUT auf den NAS (nas.dodidis-media.de → Tailscale → NAS)
       → Größenabgleich → Puffer löschen
```

Der Upload gilt für den Nutzer als **fertig, sobald er am vServer ist**
(schnellster gefühlter Upload). Die NAS-Sicherung läuft danach im Hintergrund;
im Zeitfenster ist die Datei auch direkt vom vServer ladbar (tusd GET).

## Zutaten auf dem vServer

```bash
sudo apt update && sudo apt install -y jq curl
```

### 1. tusd-Binary installieren
Neueste Version von https://github.com/tus/tusd/releases (Linux amd64):
```bash
VER=2.8.0   # aktuelle Release-Version eintragen
curl -fsSL "https://github.com/tus/tusd/releases/download/v${VER}/tusd_linux_amd64.tar.gz" \
  | sudo tar xz -C /usr/local/bin --strip-components=1 tusd_linux_amd64/tusd
tusd --version
```

### 2. Verzeichnisse + Hooks
```bash
sudo mkdir -p /var/lib/tusd-data /etc/tusd/hooks /var/log/tusd
# alle Hooks aus diesem Ordner nach /etc/tusd/hooks kopieren:
sudo cp hooks/pre-create hooks/post-finish hooks/nas-push.sh hooks/nas-retry.sh /etc/tusd/hooks/
sudo chmod +x /etc/tusd/hooks/pre-create /etc/tusd/hooks/post-finish \
              /etc/tusd/hooks/nas-push.sh /etc/tusd/hooks/nas-retry.sh
```
- `pre-create` — lehnt Uploads ohne gültiges Token VOR dem Speichern ab
  (verhindert, dass Fremde den vServer volllaufen lassen).
- `post-finish` → `nas-push.sh` — schiebt fertige Datei auf den NAS (mit Retry
  + Größenabgleich), löscht dann den Puffer.
- `nas-retry.sh` — vom Timer (Schritt 6) genutzt.

### 3. Secrets
```bash
sudo cp tusd.env.example /etc/tusd/tusd.env
sudo nano /etc/tusd/tusd.env      # API_KEY + NAS_PASS ausfüllen
sudo chmod 600 /etc/tusd/tusd.env
```

### 4. systemd-Service
```bash
sudo cp tusd.service /etc/systemd/system/tusd.service
sudo systemctl daemon-reload
sudo systemctl enable --now tusd
sudo systemctl status tusd            # sollte "active (running)" sein
journalctl -u tusd -f                 # Logs live
```

### 5. Caddy-Route
Im Caddyfile (vServer) den bestehenden Block so ergänzen — `/files/*` geht an
tusd (lokal), der Rest weiter an den NAS-WebDAV:

```caddy
nas.dodidis-media.de {
    handle /files/* {
        reverse_proxy 127.0.0.1:1080
    }
    handle {
        reverse_proxy 100.97.62.62:5005
    }
}
```
Neu laden:
```bash
docker exec caddy caddy reload --config /etc/caddy/Caddyfile   # oder: docker restart caddy
```

### 6. Retry-Timer (fängt fehlgeschlagene NAS-Pushs auf)
Falls der NAS-Push transient fehlschlägt, bleibt der Puffer liegen; dieser Timer
versucht ihn alle 10 Min erneut zu sichern, bis er auf dem NAS ist.
```bash
sudo cp nas-retry.service nas-retry.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now nas-retry.timer
systemctl list-timers nas-retry.timer      # nächste Ausführung sehen
```

## Test
1. Im Tool eine Rohdatei **> 1 GB** hochladen → Fortschritt läuft, Upload gilt
   schnell als fertig.
2. `journalctl -u tusd -f` und `tail -f /var/log/tusd/push.log` beobachten →
   `PUT → …` dann `OK — komplett auf NAS …`.
3. Datei erscheint in der Medienliste (Hintergrund-Aktualisierung), Puffer unter
   `/var/lib/tusd-data` ist danach weg.

## Fehlersuche
- **CORS-Fehler im Browser** beim `POST …/files/`: tusd erlaubt standardmäßig
  alle Origins; falls doch, in `tusd.service` `-cors-allow-origin '.*'` ergänzen.
- **`push.log`: PUT fehlgeschlagen (HTTP 401/403)** → `NAS_USER/NAS_PASS` in
  `tusd.env` prüfen.
- **`push.log`: Token-Prüfung fehlgeschlagen** → `APP_API_KEY` ≠ `config.php`
  `api_key`.
- **Puffer bleibt liegen** (Größenabgleich): NAS voll oder Übertragung abgebrochen
  — `push.log` zeigt die Größen. Datei kann manuell erneut angestoßen werden.
