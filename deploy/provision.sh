#!/usr/bin/env bash
#
# One-time server setup for a fresh Ubuntu 24.04 box.
# Run as root:  bash provision.sh yourdomain.com
#
# Installs nginx, PHP 8.3, MySQL, Composer, Node and certbot, then creates the
# deploy user and the web root. It does NOT pull the application; deploy.sh does.

set -euo pipefail

DOMAIN="${1:-}"
APP_USER="${APP_USER:-deploy}"
APP_DIR="/var/www/inventory"

if [ -z "$DOMAIN" ]; then
    echo "Usage: bash provision.sh yourdomain.com" >&2
    exit 1
fi

if [ "$(id -u)" -ne 0 ]; then
    echo "Run this as root." >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "==> Packages"
apt-get update -qq
apt-get install -y -qq software-properties-common curl git unzip
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    nginx mysql-server certbot python3-certbot-nginx \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd

echo "==> Swap (composer runs out of memory on a 1 GB box without it)"
if [ ! -f /swapfile ]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "==> Composer"
if ! command -v composer > /dev/null; then
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

echo "==> Node (only needed if you build assets on the server)"
if ! command -v node > /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y -qq nodejs
fi

echo "==> Deploy user and web root"
id -u "$APP_USER" > /dev/null 2>&1 || adduser --disabled-password --gecos "" "$APP_USER"
usermod -aG www-data "$APP_USER"
mkdir -p "$APP_DIR"
chown -R "$APP_USER:www-data" "$APP_DIR"

echo "==> nginx vhost"
sed "s/__DOMAIN__/$DOMAIN/g; s#__APP_DIR__#$APP_DIR#g" \
    "$(dirname "$0")/nginx.conf.example" > /etc/nginx/sites-available/inventory
ln -sf /etc/nginx/sites-available/inventory /etc/nginx/sites-enabled/inventory
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

cat <<EOF

Done. Next:

  1. Point the DNS A record for $DOMAIN at this server, and wait for it to resolve.
  2. Create the database:
       mysql -e "CREATE DATABASE inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
       mysql -e "CREATE USER 'inventory'@'localhost' IDENTIFIED BY 'CHANGE_ME';"
       mysql -e "GRANT ALL ON inventory.* TO 'inventory'@'localhost';"
  3. Deploy:  su - $APP_USER -c "bash $APP_DIR/deploy/deploy.sh"
  4. HTTPS:   certbot --nginx -d $DOMAIN -d www.$DOMAIN

Certbot needs BOTH names to exist in the vhost or the whole renewal fails, which
is why www is already in the server_name line.
EOF
