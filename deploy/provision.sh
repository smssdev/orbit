#!/usr/bin/env bash
#
# One-time setup of a fresh Ubuntu 24.04 VPS (e.g. Hostinger) for Orbit. Run it as root:
#
#   curl -fsSL https://raw.githubusercontent.com/smssdev/orbit/main/deploy/provision.sh -o provision.sh
#   less provision.sh            # read it first
#   bash provision.sh orbit.smszs.tech you@example.com
#
# It installs PHP 8.4 (FPM), nginx, SQLite and certbot, creates a `deploy` user that GitHub Actions logs in as,
# prepares /var/www/orbit (releases/, shared/.env, shared/storage, shared/database.sqlite), configures nginx for
# the domain and requests a Let's Encrypt certificate. Running it again is safe: every step checks first.
set -euo pipefail

DOMAIN="${1:?Usage: provision.sh <domain> <email for Let's Encrypt>}"
EMAIL="${2:?Usage: provision.sh <domain> <email for Let's Encrypt>}"
APP_DIR=/var/www/orbit
DEPLOY_USER=deploy
PHP=8.4

if [[ $EUID -ne 0 ]]; then
    echo "Run this as root." >&2
    exit 1
fi

echo "==> Packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -yq software-properties-common ca-certificates curl unzip rsync sqlite3 nginx certbot python3-certbot-nginx ufw
if ! apt-cache policy | grep -q ondrej/php; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -q
fi
apt-get install -yq "php${PHP}-fpm" "php${PHP}-cli" "php${PHP}-sqlite3" "php${PHP}-mbstring" "php${PHP}-xml" \
    "php${PHP}-curl" "php${PHP}-zip" "php${PHP}-intl" "php${PHP}-bcmath" "php${PHP}-gd"

echo "==> Firewall (SSH, HTTP, HTTPS)"
ufw allow OpenSSH >/dev/null
ufw allow 'Nginx Full' >/dev/null
ufw --force enable >/dev/null

echo "==> Deploy user"
if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
    adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi
usermod -aG www-data "$DEPLOY_USER"
install -d -m 700 -o "$DEPLOY_USER" -g "$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"
if [[ ! -f "/home/$DEPLOY_USER/.ssh/github_actions" ]]; then
    sudo -u "$DEPLOY_USER" ssh-keygen -q -t ed25519 -N "" -C "github-actions@orbit" -f "/home/$DEPLOY_USER/.ssh/github_actions"
    cat "/home/$DEPLOY_USER/.ssh/github_actions.pub" >> "/home/$DEPLOY_USER/.ssh/authorized_keys"
    chown "$DEPLOY_USER:$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh/authorized_keys"
    chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys"
fi
# The deploy user reloads PHP-FPM after each release (to clear OPcache), and nothing else.
echo "$DEPLOY_USER ALL=(root) NOPASSWD: /usr/bin/systemctl reload php${PHP}-fpm" > /etc/sudoers.d/orbit-deploy
chmod 440 /etc/sudoers.d/orbit-deploy

echo "==> App directories"
install -d -o "$DEPLOY_USER" -g www-data -m 2775 "$APP_DIR" "$APP_DIR/releases" "$APP_DIR/shared"
for dir in app/public framework/cache/data framework/sessions framework/views logs; do
    install -d -o "$DEPLOY_USER" -g www-data -m 2775 "$APP_DIR/shared/storage/$dir"
done
if [[ ! -f "$APP_DIR/shared/database.sqlite" ]]; then
    install -o "$DEPLOY_USER" -g www-data -m 664 /dev/null "$APP_DIR/shared/database.sqlite"
fi
if [[ ! -f "$APP_DIR/shared/.env" ]]; then
    cat > "$APP_DIR/shared/.env" <<ENV
APP_NAME=Orbit
APP_ENV=production
APP_KEY=base64:$(head -c 32 /dev/urandom | base64)
APP_DEBUG=false
APP_URL=https://${DOMAIN}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=sqlite
DB_DATABASE=${APP_DIR}/shared/database.sqlite

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

# Invitations are written to the log until a real mailer is configured here.
MAIL_MAILER=log
MAIL_FROM_ADDRESS="no-reply@${DOMAIN}"
MAIL_FROM_NAME="Orbit"
ENV
    chown "$DEPLOY_USER:www-data" "$APP_DIR/shared/.env"
    chmod 640 "$APP_DIR/shared/.env"
fi

echo "==> nginx"
# Written once: certbot adds the HTTPS part to this file, and a rewrite would drop it.
if [[ ! -f "/etc/nginx/sites-available/${DOMAIN}" ]]; then
cat > "/etc/nginx/sites-available/${DOMAIN}" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${APP_DIR}/current/public;
    index index.php;
    charset utf-8;
    client_max_body_size 10m;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|\$) {
        fastcgi_pass unix:/run/php/php${PHP}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX
fi
ln -sf "/etc/nginx/sites-available/${DOMAIN}" "/etc/nginx/sites-enabled/${DOMAIN}"
nginx -t
systemctl reload nginx

echo "==> HTTPS certificate"
if certbot certificates 2>/dev/null | grep -q "Domains: ${DOMAIN}"; then
    echo "Certificate already present."
elif getent hosts "$DOMAIN" >/dev/null; then
    certbot --nginx --non-interactive --agree-tos --redirect -m "$EMAIL" -d "$DOMAIN" \
        || echo "certbot failed: check that ${DOMAIN} points to this server, then run this script again."
else
    echo "${DOMAIN} does not resolve yet. Add the DNS A record, wait a few minutes, then run this script again."
fi

echo "==> Scheduler"
echo "* * * * * ${DEPLOY_USER} cd ${APP_DIR}/current && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/orbit

cat <<DONE

Done. Add these secrets in GitHub (smssdev/orbit -> Settings -> Secrets and variables -> Actions):

  SSH_HOST  this server's IP address
  SSH_USER  ${DEPLOY_USER}
  SSH_KEY   the private key below (all of it, including the BEGIN and END lines)

DONE
cat "/home/$DEPLOY_USER/.ssh/github_actions"
echo
echo "Then re-run the latest 'deploy' workflow in GitHub Actions (or push to main)."
