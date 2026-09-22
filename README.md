# Dokploy - WordPress Stack

Docker stack for WordPress with Nginx, PHP-FPM, MariaDB and Redis, optimized for Dokploy and Traefik.

## Technologies

- **Nginx 1.29** - Web server with security configurations
- **PHP 8.4-FPM** - With complete WordPress extensions
- **MariaDB 11.8** - Optimized database
- **Redis 7** - Object cache (`redis-cache` plugin)
- **Composer 2.10.3** - PHP dependency manager
- **WP-CLI** - WordPress command line interface
- **Node.js 24** - For asset building
- **msmtp** - Outbound mail from `SMTP_*`
- **wp-cron** - Runs due WordPress events every 60 seconds

## Features

### Performance
- Redis object cache, with `object-cache.php` copied on setup
- Configured OPcache (`php.ini` in production, `php.dev.ini` for development)
- MariaDB optimized for WordPress
- Static file compression and caching
- `wp-cron` container because `DISABLE_WP_CRON` is on

### Security
- Rate limiting against DDoS
- Security headers (XSS, CSRF, Clickjacking)
- Malicious bot blocking
- Dangerous PHP functions disabled
- Sensitive file blocking

### Development
- Integrated WP-CLI
- Centralized logs
- Debug mode configurable via .env
- CLI container runs `composer install` on start (`CLI_BOOTSTRAP=1`)
- Optional theme build when `THEME_DIR` is set
- Adminer and Mailpit behind the Compose profile `dev`

### Operations
- Automated installation scripts
- Database backup and restore (`scripts/backup.sh`, `scripts/restore.sh`)
- Health checks for Nginx, PHP-FPM, MariaDB and Redis
- SMTP via msmtp, disabled unless `SMTP_ENABLED=true`

## Quick Setup

### 1. Clone and configure

```bash
# Copy configuration example
cp .env.example .env

# Edit with your credentials
nano .env
```

### 2. Configure .env

```env
APP_NAME=mysite
APP_DOMAIN=mysite.com
APP_ENV=production  # or development

MYSQL_DATABASE=mysite
MYSQL_USER=your_user
MYSQL_PASSWORD=secure_password_here
MYSQL_ROOT_PASSWORD=secure_root_password
```

### 3. Start containers

```bash
# Build
docker-compose build

# Start
docker-compose up -d
```

### 4. Install and Configure

```bash
# Install dependencies via Composer
docker compose exec cli composer install

# The command above automatically runs the WordPress setup
```

**Important:** Generate salt keys at https://api.wordpress.org/secret-key/1.1/salt/

### 5. Access

- **WordPress:** http://your-domain.com (or http://localhost)

## Project Structure

```
dokploy/
|-- .docker/
|   |-- nginx/              # Nginx configuration
|   |-- php/                # Dockerfile, php.ini, php.dev.ini
|   |-- php-cli/            # CLI Dockerfile for development
|   |-- mariadb/            # MariaDB configuration
|   |-- msmtp/              # SMTP config written at container start
|-- scripts/                # backup.sh and restore.sh
|-- app/                    # Project PHP code
|   |-- Command/            # Symfony CLI commands
|   |-- Config/             # Application configurations
|   |-- Security/           # Headers and safe login
|   |-- Service/            # Services (Environment, Cache)
|-- public/                 # WordPress
|   |-- wp-core/            # WordPress core
|   |-- wp-content/         # Plugins, themes, uploads
|-- vendor/                 # Composer dependencies
|-- .env                    # Configuration (not versioned)
|-- .env.example            # Configuration template
|-- composer.json           # Project dependencies
|-- console                 # Project CLI
|-- docker-compose.yml
|-- README.md
```

## Available Commands

### Project Console
```bash
# Complete setup (copy configs + install WordPress + configure language)
docker compose exec cli php console dokpress:setup

# Copy configuration files
docker compose exec cli php console dokpress:copy-config-files

# Update salt keys in .env
docker compose exec cli php console dokpress:update-salts

# Deploy WordPress (install core, languages, plugins)
docker compose exec cli php console dokpress:wordpress-deploy

# Build the theme in THEME_DIR, when that variable is set
docker compose exec cli php console dokpress:theme-setup
```

### Backup

Dumps are written to `DB_BACKUP_DIR` from `.env`.

```bash
./scripts/backup.sh
./scripts/restore.sh /path/in/DB_BACKUP_DIR/<file>.sql.gz
```

### Docker Compose
```bash
# View logs
docker-compose logs -f

# Service-specific logs
docker-compose logs -f php-fpm

# Rebuild
docker-compose build --no-cache
docker-compose up -d

# Stop all
docker-compose down
```

### WP-CLI
```bash
# Access container
docker exec -it php-fpm bash

# WP-CLI commands
wp core version
wp plugin list
wp cache flush
```

### Composer and Node
```bash
docker exec -it php-fpm bash
composer install
npm install
yarn build
```

## Security

### Implemented Configurations

**Nginx:**
- Rate limiting (10 req/s general, 5 req/min login)
- Complete security headers
- Malicious bot blocking
- Sensitive file blocking (.sql, .git, etc)

**PHP:**
- Dangerous functions disabled
- `expose_php = Off`
- Secure sessions
- Error logging enabled

**MariaDB:**
- Credentials via environment variables
- `local_infile = 0`
- Charset utf8mb4

**WordPress (app/wp.php):**
- `DISALLOW_FILE_EDIT = true`
- Automatic HTTPS detection via proxy
- Limited revisions
- Optimized auto-save

### Pre-Production Checklist

- [ ] Change all passwords in `.env`
- [ ] Run `php console dokpress:update-salts`
- [ ] Set `APP_ENV=production` and `WP_DEBUG=false`
- [ ] Set `APP_URL` to HTTPS
- [ ] Configure HTTPS via Traefik
- [ ] Leave `COMPOSE_PROFILES` unset (Adminer and Mailpit stay off)
- [ ] Point `SMTP_*` at the production relay
- [ ] Configure backups with `scripts/backup.sh`

## Traefik and HTTPS

This stack is configured to work with **Traefik** (managed by Dokploy).

Traefik handles:
- Automatic SSL/TLS certificates (Let's Encrypt)
- HTTP to HTTPS redirection
- Load balancing
- HTTPS detection via `X-Forwarded-Proto`

## Monitoring

### Automatic Health Checks
- **Nginx:** `GET /health` returns `ok`
- **Redis:** `redis-cli ping`
- **MariaDB:** `healthcheck.sh --connect`
- **PHP-FPM:** `php-fpm` process check

### Logs
```bash
# All logs
docker-compose logs -f

# Specific logs
docker-compose logs -f nginx
docker-compose logs -f php-fpm
docker-compose logs -f mariadb
docker-compose logs -f redis
docker-compose logs -f wp-cron
```

## Performance

### Configured Cache
- **Redis:** 256MB, LRU policy
- **OPcache:** 128MB, 10k files
- **MariaDB:** Query cache 64MB
- **Nginx:** Static cache with expiration

### MariaDB Optimizations
- Buffer pool: 512MB
- Query cache: 64MB
- Max connections: 100
- Charset: utf8mb4

## Local tools

With `COMPOSE_PROFILES=dev` in `.env`:

- Adminer: http://localhost:8080 (server `mariadb`)
- Mailpit: http://localhost:8025

For Mailpit, set `SMTP_ENABLED=true`, `SMTP_HOST=mailpit`, `SMTP_PORT=1025`, `SMTP_AUTH=off` and `SMTP_TLS=off`.

## Development Environment

`php.ini` keeps OPcache from revalidating files. For local development, mount `php.dev.ini` on `php-fpm` and `cli`:

```yaml
- ./.docker/php/php.dev.ini:/usr/local/etc/php/conf.d/custom.ini
```

**php.dev.ini includes:**
- `display_errors = On`
- `opcache.validate_timestamps = 1`
- Constant revalidation

## Installed PHP Extensions

- gd (images: PNG, JPEG, WebP, XPM)
- mysqli, pdo_mysql (database)
- opcache (performance)
- curl (HTTP requests)
- zip (compression)
- exif (image metadata)
- intl (internationalization)
- bcmath (precise calculations)
- soap (web services)
- sockets (connections)
- mbstring (multibyte strings)
- xml (XML processing)
- redis (cache via PECL)

## Troubleshooting

### WordPress not loading
```bash
# Check logs
docker-compose logs nginx php-fpm

# Check files
ls -la public/

# Check permissions
docker exec -u root php-fpm chown -R www-data:www-data /var/www/html
```

### Database not connecting
```bash
# Check if MariaDB is running
docker ps | grep mariadb

# Test connection
docker exec mariadb mysql -uroot -p${MYSQL_ROOT_PASSWORD} -e "SHOW DATABASES;"

# Check credentials in wp-config.php
```

### Redis not working
```bash
# Check status
docker exec redis redis-cli ping

# View information
docker exec redis redis-cli INFO
```

### Clean and restart
```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

## Additional Documentation

- [WordPress Documentation](https://wordpress.org/documentation/)
- [WP-CLI](https://wp-cli.org/)
- [Docker Compose](https://docs.docker.com/compose/)

## License

This project is open source.
