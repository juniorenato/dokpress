# Dokpress

WordPress project template for [Dokploy](https://dokploy.com/). The stack is Nginx, PHP-FPM, MariaDB and Redis, with Traefik terminating TLS.

PHP sources use [phpDocumentor](https://docs.phpdoc.org/) DocBlocks: a one-sentence summary, an optional description, then `@param`, `@return`, `@throws`, `@var` and `@link`. Types match the PHP 8 signatures (`bool`, `int`, `string`, `array<int, string>`, `never`).

## Stack

- **Nginx 1.29** — web server
- **PHP 8.4-FPM** — WordPress runtime
- **MariaDB 11.8** — database
- **Redis 7** — object cache (`redis-cache` plugin), 256 MB, `allkeys-lru`
- **Composer 2.10.3** — PHP dependencies
- **WP-CLI** — available in the `cli` and `wp-cron` images
- **Node.js 24** — theme asset builds in the `cli` image
- **msmtp** — outbound mail from `SMTP_*`
- **wp-cron** — runs due WordPress events every 60 seconds

Composer installs `roots/wordpress` into `public/wp-core/` and these plugins into `public/wp-content/plugins/`: `redis-cache`, `wordfence`, `wordpress-seo`.

## What the stack does

### Performance

- Redis object cache. Setup copies `object-cache.php` into `public/wp-content/` when the plugin is installed.
- OPcache: 128 MB and 10k files. `php.ini` does not revalidate timestamps. Mount `php.dev.ini` locally so it does.
- Nginx caches static files and compresses responses.
- `DISABLE_WP_CRON` is on. The `wp-cron` service replaces the pseudo-cron.

### Security

Nginx (`default.conf.template`):

- Rate limits: 100 req/s general, 100 req/s admin, 5 req/min on the login location, 1 req/min on `xmlrpc.php`
- Security headers: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`
- Known scanner user agents are rejected
- Sensitive paths are denied (`.git`, dumps, and similar)

PHP (`php.ini`):

- `expose_php = Off`
- `disable_functions` blocks `exec`, `passthru`, `shell_exec`, `system`, `popen`, `show_source`

WordPress (`app/wp.php`, copied to `wp-config.php`):

- HTTPS is detected from `X-Forwarded-Proto` / `X-Forwarded-SSL`
- `DISALLOW_FILE_EDIT` and `DISALLOW_FILE_MODS` follow `WP_BLOCK_UPDATE`
- `FORCE_SSL_ADMIN` is on in production
- Post revisions are limited to 3
- External HTTP can be limited with `WP_HTTP_BLOCK_EXTERNAL` and `WP_ACCESSIBLE_HOSTS`

`App\Security\Headers` can add a second set of headers (HSTS, COOP, CSP and the Nginx headers above). `App\Security\SafeLogin` hides `wp-login.php` behind `WP_LOGIN_URL`. Neither class is constructed by this template. Load them from `public/wp-content/mu-plugins/dokpress-bootstrap.php` when you want them. That must-use plugin is not shipped here.

### Development

- `APP_ENV=development` and `WP_DEBUG=true` in `.env.example`
- The `cli` service runs `composer install` on start (`CLI_BOOTSTRAP=1`)
- When `THEME_DIR` is set, that same entrypoint runs `npm ci` and `npm run build` in the theme
- Adminer and Mailpit start only with the Compose profile `dev`

## Quick setup

### 1. Configure

```bash
cp .env.example .env
```

Set at least:

```env
APP_NAME=mysite
APP_DOMAIN=mysite.local
APP_URL=http://mysite.local
APP_ENV=development

MARIADB_DATABASE=mysite
MARIADB_USER=dev
MARIADB_PASSWORD=change-me
MARIADB_ROOT_PASSWORD=change-me
```

`DB_*` in `.env.example` points at those MariaDB variables. WordPress reads `DB_*`, not `MYSQL_*`.

Leave `COMPOSE_PROFILES` unset on Dokploy. `COMPOSE_PROFILES=dev` publishes Adminer and Mailpit.

### 2. Start

```bash
docker compose up -d --build
```

The `cli` container runs `composer install`. Composer then runs `php console dokpress:setup`, which:

1. Copies `.env.example` to `.env` only if `.env` is missing
2. Copies `app/wp.php` to `public/wp-core/wp-config.php`
3. Copies the Redis object-cache drop-in when the plugin exists
4. Installs WordPress if it is not installed, in `pt_BR`, and flushes rewrites

`dokpress:setup` does not refresh salts and does not build the theme. Run those commands yourself.

Generate salts before production:

```bash
docker compose exec cli php console dokpress:update-salts
```

`dokpress:wordpress-deploy` also runs `wp config shuffle-salts` against the copied `wp-config.php`. Run `dokpress:copy-config-files` again if that rewrite should be replaced by `app/wp.php`.

### 3. Open the site

- WordPress: the URL in `APP_URL`
- With `COMPOSE_PROFILES=dev`: Adminer at http://localhost:8080 (server `mariadb`), Mailpit at http://localhost:8025

Mailpit needs `SMTP_ENABLED=true`, `SMTP_HOST=mailpit`, `SMTP_PORT=1025`, `SMTP_AUTH=off`, `SMTP_TLS=off` and `SMTP_TLS_STARTTLS=off`.

## Project layout

```
dokpress/
|-- .docker/
|   |-- nginx/          # Nginx image and site template
|   |-- php/            # PHP-FPM image, php.ini, php.dev.ini
|   |-- php-cli/        # CLI image (Composer, WP-CLI, Node)
|   |-- mariadb/        # custom.cnf
|   |-- msmtp/          # configure.php, run at container start
|-- app/
|   |-- Command/        # Symfony console commands
|   |-- Config/         # Default Content-Security-Policy
|   |-- Security/       # Headers and SafeLogin
|   |-- Service/        # Environment and PageCache
|   |-- bootstrap.php   # Composer autoload and .env
|   |-- wp.php          # wp-config template
|-- public/
|   |-- index.php
|   |-- wp-core/        # WordPress core (Composer)
|   |-- wp-content/     # plugins, themes, uploads
|-- scripts/            # backup.sh, restore.sh
|-- tests/EnvironmentTest.php
|-- console
|-- composer.json
|-- docker-compose.yml
|-- .env.example
```

## Application code

| Symbol | Role |
| --- | --- |
| `App\Service\Environment` | Reads env vars. Casts `true` / `false`. `production()`, `staging()`, `local()`. `dev()` and `development()` alias `local()`. |
| `App\Service\PageCache` | Filesystem page cache in `app/Cache` (1 hour, tag `page`). Not hooked into WordPress. |
| `App\Config\Setup` | Default Content-Security-Policy string. |
| `App\Security\Headers` | Optional response headers from `ENABLE_*` flags. |
| `App\Security\SafeLogin` | Custom login path from `WP_LOGIN_URL`. Inactive when that variable is empty. |
| `console` | Loads every class in `app/Command`. |

`WD_BASE_PATH` is the project root. `console` and `app/wp.php` define it before including `app/bootstrap.php`.

## Commands

Run them in the `cli` service. WP-CLI is not installed in `php-fpm`.

```bash
docker compose exec cli php console dokpress:setup
docker compose exec cli php console dokpress:copy-config-files
docker compose exec cli php console dokpress:update-salts
docker compose exec cli php console dokpress:wordpress-deploy
docker compose exec cli php console dokpress:theme-setup
```

`dokpress:theme-setup` reads `THEME_DIR`. It runs `composer install` when the theme has `composer.json`, and `npm ci` plus `npm run build` when it has `package.json`. An empty `THEME_DIR` succeeds without doing anything.

### Backup

Dumps go to `DB_BACKUP_DIR` from `.env`, using `MARIADB_DATABASE`, `MARIADB_USER` and `MARIADB_PASSWORD`.

```bash
./scripts/backup.sh
./scripts/restore.sh /absolute/path/to/dump.sql.gz
```

### Compose

```bash
docker compose logs -f
docker compose logs -f php-fpm
docker compose up -d --build
docker compose down
```

### WP-CLI

```bash
docker compose exec cli wp core version --path=public/wp-core --allow-root
docker compose exec cli wp plugin list --path=public/wp-core --allow-root
docker compose exec cli wp cache flush --path=public/wp-core --allow-root
```

## Environment

See `.env.example` for the full list. Groups:

- **Application:** `APP_NAME`, `APP_DOMAIN`, `APP_URL`, `APP_ENV`, `COMPOSE_PROFILES`, `HTTP_PORT`, `HTTPS_PORT`
- **MariaDB:** `MARIADB_DATABASE`, `MARIADB_USER`, `MARIADB_PASSWORD`, `MARIADB_ROOT_PASSWORD`
- **WordPress:** `WP_DEBUG`, `WP_ADMIN_USER`, `WP_ADMIN_PASSWORD`, `WP_ADMIN_EMAIL`, `WP_BLOCK_UPDATE`, `WP_HTTP_BLOCK_EXTERNAL`, `WP_ACCESSIBLE_HOSTS`, `WP_LOGIN_URL`, `WP_UPLOADS_DIR`, `THEME_DIR`
- **Redis:** `REDIS_HOST`, `REDIS_PORT`, `REDIS_PREFIX`
- **SMTP:** `SMTP_ENABLED`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_FROM`, `SMTP_AUTH`, `SMTP_TLS`, `SMTP_TLS_STARTTLS`, `SMTP_USER`, `SMTP_PASSWORD`
- **Salts:** `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`, `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`
- **Database for WordPress:** `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST`, `DB_CHARSET`, `DB_COLLATE`, `DB_TABLE_PREFIX`, `DB_BACKUP_DIR`
- **PHP security headers:** `ENABLE_CONTENT_SECURITY_POLICY`, `ENABLE_STRICT_TRANSPORT_SECURITY`, `ENABLE_X_CONTENT_TYPE_OPTION`, `ENABLE_X_FRAME_OPTION`, `ENABLE_X_XSS_PROTECTION`, `ENABLE_REFERRER_POLICY`, `ENABLE_PERMISSIONS_POLICY`, `ENABLE_CROSS_ORIGIN_EMBEDDER_POLICY`, `ENABLE_CROSS_ORIGIN_OPENER_POLICY`, plus optional override values

`Environment::get()` treats an empty variable as missing and returns the given default. The strings `true`, `(true)`, `false` and `(false)` become booleans.

## Production checklist

- [ ] Replace the development passwords in `.env`
- [ ] Run `php console dokpress:update-salts`
- [ ] Set `APP_ENV=production` and `WP_DEBUG=false`
- [ ] Set `APP_URL` to `https://…`
- [ ] Leave `COMPOSE_PROFILES` unset
- [ ] Point `SMTP_*` at the production relay (`SMTP_AUTH=on`, `SMTP_TLS=on`)
- [ ] Schedule `scripts/backup.sh`

Traefik, managed by Dokploy, terminates TLS, redirects HTTP to HTTPS and sets `X-Forwarded-Proto`.

## Health and logs

| Service | Check |
| --- | --- |
| nginx | `GET /health` returns `ok` |
| php-fpm | `php-fpm` process is running |
| mariadb | `healthcheck.sh --connect` |
| redis | `redis-cli ping` |

```bash
docker compose logs -f nginx php-fpm mariadb redis wp-cron
```

## Local PHP

`php.ini` sets `opcache.validate_timestamps = 0`. For local editing, mount the development ini on `php-fpm` and `cli`:

```yaml
- ./.docker/php/php.dev.ini:/usr/local/etc/php/conf.d/custom.ini
```

`php.dev.ini` turns on `display_errors` and revalidates OPcache.

PHP extensions in the images: gd, mysqli, pdo_mysql, opcache, curl, zip, exif, intl, bcmath, soap, sockets, mbstring, xml, redis.

MariaDB (`custom.cnf`): InnoDB buffer pool 512 MB, `max_connections` 100, `utf8mb4`, `local_infile = 0`. The file still sets `query_cache_*`. MariaDB 11 no longer implements the query cache; those lines are not an active cache.

## Tests

```bash
php tests/EnvironmentTest.php
```

## Troubleshooting

WordPress does not load:

```bash
docker compose logs nginx php-fpm
ls -la public/
```

Database connection fails:

```bash
docker compose ps mariadb
docker compose exec mariadb mariadb -uroot -p"${MARIADB_ROOT_PASSWORD}" -e "SHOW DATABASES;"
```

Redis:

```bash
docker compose exec redis redis-cli ping
```

SMTP stays off unless `SMTP_ENABLED` is `true` or `on` and `SMTP_HOST` is set. `configure.php` then writes `/etc/msmtprc`.

Reset volumes and images:

```bash
docker compose down -v
docker compose up -d --build
```

## License

Proprietary. See `composer.json`.
