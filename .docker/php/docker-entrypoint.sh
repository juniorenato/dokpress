#!/bin/sh
set -e

php /usr/local/bin/configure-msmtp.php

if [ "$(id -u)" = "0" ]; then
  echo "==> chown 1000:1000 /var/www (o .git permanece do root)"
  mkdir -p \
    /var/www/public/wp-content/languages \
    /var/www/public/wp-content/upgrade \
    /var/www/public/wp-content/cache \
    /var/www/public/wp-content/wflogs
  if [ -d /var/www/.git ]; then
    chown -R root:root /var/www/.git
  fi
  find /var/www -path /var/www/.git -prune -o -exec chown 1000:1000 {} +
  if [ -d /var/log/php ]; then
    chown -R 1000:1000 /var/log/php
  fi
  echo "==> permissões aplicadas"
fi

exec docker-php-entrypoint "$@"
