#!/usr/bin/env bash
set -u

APP_DIR="/home/contact/web/manage.ns-conseil.com"
REPORT_DIR="$APP_DIR/storage/reports"
REPORT_FILE="$REPORT_DIR/phone-search-weekly.log"
LOCK_FILE="/tmp/manage-ns-conseil-phone-search-smoke.lock"
PROSPECT_ID="${PHONE_SEARCH_SMOKE_PROSPECT_ID:-3438}"
BASE_URL="${PHONE_SEARCH_SMOKE_BASE_URL:-https://manage.ns-conseil.com}"

mkdir -p "$REPORT_DIR"
exec 9>"$LOCK_FILE"
flock -n 9 || exit 0

{
    printf '\n[%s] Démarrage du rapport hebdomadaire\n' "$(date --iso-8601=seconds)"
    printf 'prospect_test_id=%s\n' "$PROSPECT_ID"

    cd "$APP_DIR" || {
        echo 'result=failed reason=application_directory_unavailable'
        exit 1
    }

    if php artisan crm:phone-search-smoke --prospect-id="$PROSPECT_ID"; then
        echo 'phone_search_smoke=passed'
    else
        echo 'phone_search_smoke=failed'
        exit 1
    fi

    HTTP_STATUS=$(curl --silent --show-error --max-time 20 --output /dev/null --write-out '%{http_code}' "$BASE_URL/ns-conseil/login" || echo '000')
    echo "login_endpoint_http=$HTTP_STATUS"

    case "$HTTP_STATUS" in
        200|301|302|303|307|308)
            echo 'public_endpoint=passed'
            echo 'result=passed'
            ;;
        *)
            echo 'public_endpoint=failed'
            echo 'result=failed'
            exit 1
            ;;
    esac
} >> "$REPORT_FILE" 2>&1

# Conserver les 500 dernières lignes afin d’éviter une croissance sans limite.
tail -n 500 "$REPORT_FILE" > "$REPORT_FILE.tmp" && mv "$REPORT_FILE.tmp" "$REPORT_FILE"
chown contact:contact "$REPORT_FILE" 2>/dev/null || true
chmod 664 "$REPORT_FILE" 2>/dev/null || true
