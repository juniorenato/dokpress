#!/bin/bash
set -e

# Generate SSL certificates if they do not exist
if [ ! -f /etc/nginx/certs/${APP_DOMAIN}.crt ]; then
  echo "Generating SSL certificates..."
  mkdir -p /etc/nginx/certs
  openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/certs/${APP_DOMAIN}.key \
    -out /etc/nginx/certs/${APP_DOMAIN}.crt \
    -subj "/C=BR/ST=State/L=City/O=Organization/CN=${APP_NAME}"
  chmod 644 /etc/nginx/certs/${APP_DOMAIN}.crt
  chmod 600 /etc/nginx/certs/${APP_DOMAIN}.key
  echo "SSL certificates generated successfully"
else
  echo "SSL certificates already exist"
fi

# Verificar se existe um template de site.conf
if [ -f /etc/nginx/templates/site.conf.template ]; then
    echo "Processando template site.conf.template com variáveis de ambiente"
    envsubst '${APP_DOMAIN}' < /etc/nginx/templates/site.conf.template > /etc/nginx/conf.d/site.conf
    echo "Template processado com sucesso"
elif [ -f /etc/nginx/conf.d/site.conf ]; then
    echo "Usando site.conf existente (sem processamento de template)"
else
    echo "AVISO: Nenhum arquivo de configuração encontrado. Usando configuração padrão do nginx."
fi

# Garantir permissões de execução em wp-content
if [ -d /var/www/public/wp-content ]; then
  echo "Ajustando permissões em /var/www/public/wp-content"
  chmod +x /var/www/public/wp-content
  chmod -R +x /var/www/public/wp-content/plugins
  chmod -R +x /var/www/public/wp-content/themes
else
  echo "Diretório /var/www/public/wp-content não encontrado, pulando chmod."
fi

# Testar configuração do nginx
nginx -t

# Executar nginx
exec nginx -g "daemon off;"
