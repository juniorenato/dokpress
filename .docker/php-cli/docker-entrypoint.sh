#!/bin/bash
set -e
cd /var/www

php /usr/local/bin/configure-msmtp.php

if [ "${CLI_BOOTSTRAP:-}" = "1" ]; then
  if [ "$(id -u)" != "0" ]; then
    echo "O bootstrap do CLI precisa de root para gravar em /var/www." >&2
    exit 1
  fi

  export COMPOSER_ALLOW_SUPERUSER=1

  echo "==> composer install"
  if [ "${APP_ENV:-production}" = "production" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
  else
    composer install --no-interaction --prefer-dist
  fi

  if [ -n "${THEME_DIR:-}" ]; then
    if [ ! -f "${THEME_DIR}/package.json" ]; then
      echo "Tema não encontrado: ${THEME_DIR}" >&2
      exit 1
    fi

    echo "==> npm ci (${THEME_DIR})"
    npm ci --prefix "${THEME_DIR}"

    echo "==> npm run build (${THEME_DIR})"
    npm run build --prefix "${THEME_DIR}"
  fi
fi

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
  chown -R 1000:1000 /home/user
  if [ -d /var/log/php ]; then
    chown -R 1000:1000 /var/log/php
  fi
  echo "==> permissões aplicadas"
  exec su-exec 1000:1000 "$@"
fi

exec "$@"
