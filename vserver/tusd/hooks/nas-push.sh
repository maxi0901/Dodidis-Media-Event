#!/usr/bin/env bash
#
# Überträgt eine am vServer fertig gepufferte Upload-Datei per WebDAV auf den
# NAS und löscht den Puffer ERST, wenn die Datei nachweislich vollständig auf
# dem NAS liegt (Größenabgleich). Läuft detached aus post-finish.
#
# Aufruf:  nas-push.sh <pufferpfad> <token>
# Env (aus /etc/tusd/tusd.env):
#   APP_BASE     z. B. https://dodidis-media.de/agenturtool
#   APP_API_KEY  = config.php['api_key'] (HMAC-/Hook-Auth)
#   NAS_BASE     z. B. https://nas.dodidis-media.de/Test   (= NAS_DAV_BASE)
#   NAS_USER / NAS_PASS  WebDAV-Zugang (toolsvc)
# Benötigt: curl, jq
set -uo pipefail

path="${1:-}"
token="${2:-}"
log() { printf '%s [nas-push] %s\n' "$(date '+%F %T')" "$1"; }

[ -n "$path" ] && [ -f "$path" ] || { log "Datei fehlt: ${path:-<leer>}"; exit 1; }
[ -n "$token" ] || { log "Token fehlt"; exit 1; }

# 1) Zielpfad vom Agenturtool bestätigen lassen (HMAC-Prüfung dort)
target="$(curl -fsS -X POST \
    -H "X-API-KEY: ${APP_API_KEY}" -H "Content-Type: application/json" \
    --data "$(jq -nc --arg t "$token" '{token:$t}')" \
    "${APP_BASE%/}/api/tus_verify.php" | jq -r '.data.target // empty')"
[ -n "$target" ] || { log "Token-/Zielprüfung fehlgeschlagen"; exit 1; }

# 2) Zielpfad URL-encoden (Slashes als Trenner behalten)
enc="$(printf '%s' "$target" | jq -sRr @uri | sed 's/%2F/\//g')"
url="${NAS_BASE%/}/${enc}"
localsize="$(stat -c%s "$path")"

# 3) Auf den NAS streamen (kein Zwischenpuffer)
log "PUT → ${target} (${localsize} B)"
code="$(curl -s -o /dev/null -w '%{http_code}' -T "$path" -u "${NAS_USER}:${NAS_PASS}" "$url")"
if [ "$code" != "201" ] && [ "$code" != "204" ]; then
    log "WebDAV-PUT fehlgeschlagen (HTTP ${code}) — Puffer bleibt für Retry"
    exit 1
fi

# 4) Vollständigkeit prüfen: NAS-Dateigröße muss exakt passen, SONST NICHT löschen
nassize="$(curl -sI -u "${NAS_USER}:${NAS_PASS}" "$url" \
    | awk 'BEGIN{IGNORECASE=1} /^content-length:/{gsub(/\r/,""); print $2}' | tail -1)"
if [ "$nassize" = "$localsize" ]; then
    rm -f "$path" "${path}.info"
    log "OK — komplett auf NAS (${nassize} B), Puffer gelöscht"
else
    log "Größenabgleich fehlgeschlagen (NAS=${nassize:-?} lokal=${localsize}) — Puffer bleibt"
    exit 1
fi
