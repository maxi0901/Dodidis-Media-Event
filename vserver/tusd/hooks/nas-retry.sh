#!/usr/bin/env bash
#
# Retry-Sweeper: pusht am vServer verbliebene, VOLLSTÄNDIGE Uploads erneut auf
# den NAS (falls der post-finish-Push transient fehlschlug). Läuft periodisch
# über den systemd-Timer nas-retry.timer. Nutzt nas-push.sh.
#
# Env (aus /etc/tusd/tusd.env): APP_BASE, APP_API_KEY, NAS_BASE, NAS_USER, NAS_PASS
# Benötigt: jq
set -uo pipefail

DIR="${TUSD_DATA_DIR:-/var/lib/tusd-data}"
HOOKS="$(dirname "$0")"
log() { printf '%s [nas-retry] %s\n' "$(date '+%F %T')" "$1"; }

shopt -s nullglob
for info in "$DIR"/*.info; do
    data="${info%.info}"
    [ -f "$data" ] || continue

    # Nur vollständige Uploads (Offset == Size) — laufende in Ruhe lassen.
    size="$(jq -r '.Size   // 0'          "$info" 2>/dev/null)"
    off="$(jq  -r '.Offset // 0'          "$info" 2>/dev/null)"
    token="$(jq -r '.MetaData.token // empty' "$info" 2>/dev/null)"
    [ "$size" != "0" ] && [ "$off" = "$size" ] || continue
    [ -n "$token" ] || continue

    log "erneuter NAS-Push: $(basename "$data") (${size} B)"
    "$HOOKS/nas-push.sh" "$data" "$token" || log "weiter fehlgeschlagen: $(basename "$data")"
done
