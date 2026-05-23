#!/usr/bin/env bash
#
# Re-deploys ganvo to production. Idempotent — safe to re-run.
#
# Run as the user that owns /var/www/ganvo (NOT root). The script puts
# the app into maintenance mode, fetches latest master, installs deps,
# runs migrations, rebuilds caches, then drops out of maintenance.
#
# First-time setup is in DEPLOY.md; this script is for everything after.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ganvo}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

cd "$APP_DIR"

echo "==> Putting app into maintenance mode"
$PHP_BIN artisan down --render="errors::503" || true

trap '$PHP_BIN artisan up || true' EXIT  # always come back up, even on script failure

echo "==> Fetching latest master"
git fetch --prune origin master
git reset --hard origin/master

echo "==> Installing PHP dependencies"
$COMPOSER_BIN install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

echo "==> Installing JS dependencies + building Vite assets"
$NPM_BIN ci --no-audit --no-fund --prefer-offline
$NPM_BIN run build

echo "==> Running database migrations"
$PHP_BIN artisan migrate --force

echo "==> Rebuilding caches"
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan event:cache
# Clear Spatie permission + Filament caches in case roles/permissions changed.
$PHP_BIN artisan permission:cache-reset || true
$PHP_BIN artisan filament:cache-components || true

echo "==> Ensuring storage symlink exists"
$PHP_BIN artisan storage:link || true

echo "==> Done. App is back up."
