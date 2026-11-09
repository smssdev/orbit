#!/usr/bin/env bash
#
# Runs on the server, as the deploy user, from inside a freshly uploaded release (called by the deploy workflow).
# Links the shared .env, storage and database, migrates, caches, then switches `current` to this release in one
# atomic step and keeps the last five releases for rollbacks.
set -euo pipefail

# Set by the deploy workflow from the APP_DIR repository variable (CloudPanel: /home/<site user>/htdocs/<domain>).
APP_DIR="${APP_DIR:-/var/www/orbit}"
RELEASE="$(pwd -P)"
PHP=8.4

case "$RELEASE" in
    "$APP_DIR"/releases/*) ;;
    *) echo "Run this from a release directory under $APP_DIR/releases." >&2; exit 1 ;;
esac

ln -sfn "$APP_DIR/shared/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage"
ln -sfn "$APP_DIR/shared/storage" "$RELEASE/storage"
mkdir -p "$RELEASE/bootstrap/cache"
chmod -R g+w "$RELEASE/bootstrap/cache"

php artisan migrate --force --no-interaction
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize

ln -sfn "$RELEASE" "$APP_DIR/current.tmp"
mv -Tf "$APP_DIR/current.tmp" "$APP_DIR/current"

sudo /usr/bin/systemctl reload "php${PHP}-fpm"

# Keep the five newest releases.
cd "$APP_DIR/releases"
ls -1dt -- */ | tail -n +6 | xargs -r rm -rf --

echo "Live: $RELEASE"
