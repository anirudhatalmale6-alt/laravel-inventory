#!/usr/bin/env bash
#
# Deploy or update the app. Run as the deploy user:
#   bash deploy/deploy.sh
#
# Safe to run repeatedly. On the first run it clones and asks you to fill in .env.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/inventory}"
REPO="${REPO:-https://github.com/anirudhatalmale6-alt/laravel-inventory.git}"
BRANCH="${BRANCH:-main}"
BUILD_ASSETS="${BUILD_ASSETS:-1}"

if [ ! -d "$APP_DIR/.git" ]; then
    echo "==> First run, cloning into $APP_DIR"
    git clone -b "$BRANCH" "$REPO" "$APP_DIR"
    cd "$APP_DIR"
    cp .env.example .env
    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan key:generate
    cat <<'EOF'

Edit .env now and set at least:

    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://yourdomain.com
    DB_CONNECTION=mysql
    DB_DATABASE=inventory
    DB_USERNAME=inventory
    DB_PASSWORD=...

Then run this script again to migrate and go live.

APP_DEBUG=false matters: left true, a stack trace on any error shows your
database credentials to whoever triggered it.
EOF
    exit 0
fi

cd "$APP_DIR"

echo "==> Pulling $BRANCH"
git fetch --quiet origin "$BRANCH"
git reset --hard "origin/$BRANCH"

echo "==> Maintenance mode"
php artisan down --render="errors::503" || true
trap 'php artisan up || true' EXIT

echo "==> Dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

if [ "$BUILD_ASSETS" = "1" ]; then
    echo "==> Assets"
    npm ci --silent
    npm run build
fi

echo "==> Database"
php artisan migrate --force

echo "==> Caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Permissions"
chmod -R ug+rw storage bootstrap/cache

echo "==> Checking the ledger still agrees with itself"
php artisan inventory:reconcile --dry-run

echo "Deployed."
