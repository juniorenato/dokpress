#!/bin/bash
set -e

cd /var/www

# Ajustar permissões para o usuário 1000
chown -R 1000:1000 /var/www /home/user 2>/dev/null || true

# Verificar se composer.json existe
if [ -f "composer.json" ]; then
    # Instalar dependências como usuário 1000 (sem dev em produção)
    if [ "$APP_ENV" = "production" ]; then
        su-exec 1000:1000 composer install --no-dev --optimize-autoloader --no-interaction
        su-exec 1000:1000 composer update --no-dev --optimize-autoloader --no-interaction
    else
        echo "Running in development mode, skipping composer install."
    fi
fi

# Executar comando passado como usuário 1000
exec su-exec 1000:1000 "$@"
