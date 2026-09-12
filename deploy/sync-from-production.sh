#!/usr/bin/env bash
#
# Pull one tenant's shop from PRODUCTION down onto this machine, in one command.
#
#   bash deploy/sync-from-production.sh
#   bash deploy/sync-from-production.sh --slug=sankevi --with-orders
#   bash deploy/sync-from-production.sh --dry-run
#
# It runs ganvo:tenant-export on the server, copies the bundle down, and runs
# ganvo:tenant-import into the local Docker container. The import REPLACES that
# tenant's local data — catalogue, store settings and uploads — and leaves every
# other tenant alone.
#
# WHY IT DROPS TO www-data ON THE SERVER. storage/ is 775 owned <user>:www-data
# so both the SSH user and the web server can write there (DEPLOY.md §5). Run
# artisan as root and whatever it touches comes out root:root — and the one that
# bites is storage/logs/laravel.log, which then stops being writable by the site
# itself. Writing the bundle to /tmp keeps it out of storage/ entirely.
#
# NOT FOR PUSHING THE OTHER WAY. There is deliberately no --up: importing onto
# production would delete the live catalogue and replace it with a laptop's copy.

set -euo pipefail

HOST="${GANVO_HOST:-root@173.249.5.42}"
APP_DIR="${GANVO_APP_DIR:-/var/www/ganvo}"
CONTAINER="${GANVO_CONTAINER:-ganvo}"
SLUG="sankevi"
WITH_ORDERS=""
ASSUME_YES=""
DRY_RUN=""

usage() {
    sed -n '2,22p' "$0" | sed 's/^#\{1,2\} \{0,1\}//'
    cat <<'USAGE'

Options
  --slug=NAME       tenant to pull (default: sankevi)
  --host=USER@HOST  ssh target (default: root@173.249.5.42, or $GANVO_HOST)
  --container=NAME  local docker container (default: ganvo, or $GANVO_CONTAINER)
  --with-orders     include orders, customers and messages (personal data)
  -y, --yes         skip the confirmation
  --dry-run         print what would run, touch nothing
  -h, --help        this
USAGE
}

for arg in "$@"; do
    case "$arg" in
        --slug=*)       SLUG="${arg#*=}" ;;
        --host=*)       HOST="${arg#*=}" ;;
        --container=*)  CONTAINER="${arg#*=}" ;;
        --with-orders)  WITH_ORDERS="--with-orders" ;;
        -y|--yes)       ASSUME_YES=1 ;;
        --dry-run)      DRY_RUN=1 ;;
        -h|--help)      usage; exit 0 ;;
        *) echo "Unknown option: $arg" >&2; echo; usage; exit 2 ;;
    esac
done

STAMP="$(date +%Y-%m-%d-%H%M%S)"
REMOTE_ZIP="/tmp/${SLUG}-${STAMP}.zip"
LOCAL_ZIP="${TMPDIR:-/tmp}/${SLUG}-${STAMP}.zip"

say()  { printf '\n\033[1;32m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  ! \033[0m%s\n' "$*"; }
run()  { if [ -n "$DRY_RUN" ]; then printf '    \033[2m%s\033[0m\n' "$*"; else eval "$@"; fi; }

# ── 0. the things that are cheaper to check than to fail on ──────────────
if [ -z "$DRY_RUN" ]; then
    command -v docker >/dev/null || { echo "docker not found on PATH" >&2; exit 1; }
    docker inspect -f '{{.State.Running}}' "$CONTAINER" 2>/dev/null | grep -q true \
        || { echo "Container '$CONTAINER' is not running. Start it, or pass --container=NAME." >&2; exit 1; }
    ssh -o BatchMode=yes -o ConnectTimeout=8 "$HOST" true 2>/dev/null \
        || { echo "Cannot ssh to $HOST without a password prompt. Check your key, or pass --host=USER@HOST." >&2; exit 1; }
fi

# ── 1. what this is about to overwrite ───────────────────────────────────
if [ -z "$ASSUME_YES" ] && [ -z "$DRY_RUN" ]; then
    cat <<PROMPT

  Pulling '$SLUG' from $HOST onto this machine.

  This REPLACES the local '$SLUG' catalogue, store settings and uploads with
  production's. Other tenants are untouched. Orders are ${WITH_ORDERS:+INCLUDED}${WITH_ORDERS:-left behind}.

PROMPT
    read -r -p "  Continue? [y/N] " reply
    case "$reply" in [yY]*) ;; *) echo "  Nothing done."; exit 0 ;; esac
fi

# ── 2. package it on the server ──────────────────────────────────────────
say "Exporting '$SLUG' on $HOST"
run "ssh '$HOST' \"cd '$APP_DIR' && sudo -u www-data php artisan ganvo:tenant-export '$SLUG' --path='$REMOTE_ZIP' $WITH_ORDERS\""

# ── 3. bring it down ─────────────────────────────────────────────────────
say "Copying the bundle down"
run "scp -q '$HOST:$REMOTE_ZIP' '$LOCAL_ZIP'"
run "ssh '$HOST' \"rm -f '$REMOTE_ZIP'\""
[ -n "$DRY_RUN" ] || echo "    $(du -h "$LOCAL_ZIP" | cut -f1)  $LOCAL_ZIP"

# ── 4. load it locally ───────────────────────────────────────────────────
say "Importing into container '$CONTAINER'"
run "docker cp '$LOCAL_ZIP' '$CONTAINER:/tmp/bundle.zip'"
run "docker exec '$CONTAINER' php artisan ganvo:tenant-import /tmp/bundle.zip"
run "docker exec '$CONTAINER' rm -f /tmp/bundle.zip"
run "docker exec '$CONTAINER' php artisan view:clear"

# ── 5. say what came down, because the store row travels too ─────────────
#
# stores is in the export graph, so order_flow, shipping_methods, theme_settings
# and rack_configurator all arrive from production. While master is ahead of
# what is deployed, that quietly undoes local-only state — the configurator
# switches off and the shop goes back to taking card payments. Report it rather
# than let it be discovered on the storefront.
if [ -z "$DRY_RUN" ]; then
    say "Store settings that came from production"
    docker exec "$CONTAINER" php artisan tinker --execute "
        \$t = \App\Models\Tenant::where('slug', '$SLUG')->first();
        \$s = \$t?->store;
        if (! \$s) { echo '  no store row for $SLUG'.PHP_EOL; return; }
        \$cfg = method_exists(\$s, 'rackConfigurator') ? \$s->rackConfigurator() : ['enabled' => null];
        printf('  order_flow   : %s%s', \$s->order_flow ?: 'payment', PHP_EOL);
        printf('  configurator : %s%s', (\$cfg['enabled'] ?? false) ? 'on' : 'OFF', PHP_EOL);
        printf('  shipping     : %s%s', collect(\$s->shippingMethods())->pluck('label')->implode(', '), PHP_EOL);
        printf('  products     : %d%s', \App\Models\Product::where('tenant_id', \$t->id)->count(), PHP_EOL);
    " 2>/dev/null | grep -E '(order_flow|configurator|shipping|products|no store)' || true

    cat <<'TAIL'

  Those four lines came from production's store row, which travels with the
  export. While master is ahead of what is deployed, that undoes local-only
  state: the configurator switches off and the shop goes back to card checkout.

  The real fix is to deploy master, after which the two sides agree. Until then,
  re-apply just the two data migrations that set it — rolling back these two is
  safe, they only rewrite the store row and the parts table:

      docker exec ganvo php artisan migrate:rollback --force \
        --path=database/migrations/2026_09_10_160000_sankevi_takes_enquiries_not_payments.php
      docker exec ganvo php artisan migrate --force \
        --path=database/migrations/2026_09_10_160000_sankevi_takes_enquiries_not_payments.php

  and the same pair for 2026_09_10_150000_seed_sankevi_rack_parts.php to switch
  the configurator back on.
TAIL
fi

say "Done."
