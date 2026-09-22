<?php

/**
 * WordPress configuration template for Dokpress.
 *
 * `dokpress:copy-config-files` copies this file to `public/wp-core/wp-config.php`.
 * Database credentials, salts and feature flags come from the environment
 * through {@see \App\Service\Environment}.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 */

use App\Service\Environment;

if(!defined('WD_BASE_PATH')) define('WD_BASE_PATH', realpath(__DIR__ . '/../..'));
if(!defined('WP_PROJECT_PATH')) define('WP_PROJECT_PATH', WD_BASE_PATH .'/public');

require_once WD_BASE_PATH .'/app/bootstrap.php';

/*
 * Treats a reverse-proxy HTTPS request as a native HTTPS request.
 * Required so FORCE_SSL_ADMIN does not redirect in a loop behind Traefik.
 */
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $_SERVER['HTTPS'] = 'on';
}

/**
 * Public site URL from `APP_URL`.
 *
 * @var string
 */
$site_url = Environment::get('APP_URL');

/**
 * Whether `APP_ENV` is production.
 *
 * @var bool
 */
$production = Environment::production();

/**
 * Whether WordPress debug mode is enabled.
 *
 * @var bool
 */
$debug = (bool) Environment::get('WP_DEBUG', false);

/** Database name for WordPress. */
define('DB_NAME', Environment::get('DB_NAME'));

/** Database user. */
define('DB_USER', Environment::get('DB_USER'));

/** Database password. */
define('DB_PASSWORD', Environment::get('DB_PASSWORD'));

/** Database host. */
define('DB_HOST', Environment::get('DB_HOST'));

/** Charset used when creating database tables. */
define('DB_CHARSET', Environment::get('DB_CHARSET'));

/** Collation used when creating database tables. */
define('DB_COLLATE', Environment::get('DB_COLLATE'));

/**#@+
 * Authentication keys and salts.
 *
 * Change these to unique phrases. Generate them with
 * {@link https://api.wordpress.org/secret-key/1.1/salt/} or
 * `php console dokpress:update-salts`. Changing them invalidates existing cookies.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         Environment::get('AUTH_KEY', 'default-salts'));
define('SECURE_AUTH_KEY',  Environment::get('SECURE_AUTH_KEY', 'default-salts'));
define('LOGGED_IN_KEY',    Environment::get('LOGGED_IN_KEY', 'default-salts'));
define('NONCE_KEY',        Environment::get('NONCE_KEY', 'default-salts'));
define('AUTH_SALT',        Environment::get('AUTH_SALT', 'default-salts'));
define('SECURE_AUTH_SALT', Environment::get('SECURE_AUTH_SALT', 'default-salts'));
define('LOGGED_IN_SALT',   Environment::get('LOGGED_IN_SALT', 'default-salts'));
define('NONCE_SALT',       Environment::get('NONCE_SALT', 'default-salts'));
/**#@-*/

/**
 * WordPress database table prefix.
 *
 * Use only numbers, letters and underscores. A distinct prefix allows more
 * than one installation in the same database.
 *
 * @var string
 */
$table_prefix = Environment::get('DB_TABLE_PREFIX');

/**
 * Enables WordPress debug mode.
 *
 * Notices are displayed only when this is true outside production.
 * The log file is `app/Log/wp-debug-YYYY-MM.log`.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-debug
 */
define('WP_DEBUG', $debug);

/**
 * Public address of the site (`APP_URL`).
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#blog-address-url
 */
define('WP_HOME', $site_url);

/**
 * Address of the WordPress core directory.
 *
 * Core lives in `public/wp-core`, so the site URL gains the `/wp-core` suffix.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-siteurl
 */
$site_url = trim($site_url, '/');
define('WP_SITEURL', $site_url .'/wp-core');

/**
 * Moves `wp-content` to `public/wp-content`, outside the core directory.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#moving-wp-content-folder
 */
define('WP_CONTENT_DIR', WP_PROJECT_PATH . '/wp-content');
define('WP_CONTENT_URL', $site_url .'/wp-content');

/**
 * Limits stored post revisions.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#post-revisions
 */
define('WP_POST_REVISIONS', 3);

/**
 * Cookie domain from `APP_DOMAIN`.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#set-cookie-domain
 */
define('COOKIE_DOMAIN', Environment::get('APP_DOMAIN'));

/**
 * Monthly debug log written under `app/Log`.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#configure-error-logging
 */
$logfile = WD_BASE_PATH .'/app/Log/wp-debug-'. date('Y-m') .'.log';
define('WP_DEBUG_LOG', $logfile);

/**
 * Prints debug output only when debug is on and the environment is not production.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG_DISPLAY', $debug && !$production);

/**
 * Lets WordPress show the fatal error handler outside production.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-disable-fatal-error-handler
 */
define('WP_DISABLE_FATAL_ERROR_HANDLER', !$production);

/**
 * WordPress environment type derived from `APP_ENV`.
 *
 * `prod` becomes `production`. `dev`, `loc` and `local` become `development`.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-environment-type
 */
$envType = Environment::get('APP_ENV', 'development');
if ($envType === 'prod') {
    $envType = 'production';
} elseif (in_array($envType, ['dev', 'loc', 'local'], true)) {
    $envType = 'development';
}
define('WP_ENVIRONMENT_TYPE', $envType);

/**
 * Loads unminified core scripts and styles when debug is on.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#script-debug
 */
define('SCRIPT_DEBUG', $debug);

/**
 * Enables the Redis object cache drop-in.
 *
 * Host, port and key prefix come from `REDIS_HOST`, `REDIS_PORT` and `REDIS_PREFIX`.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#cache
 */
define('WP_CACHE', true);
define('WP_REDIS_HOST', Environment::get('REDIS_HOST', 'redis'));
define('WP_REDIS_PORT', Environment::get('REDIS_PORT', '6379'));
define('WP_REDIS_PREFIX', Environment::get('REDIS_PREFIX', Environment::get('APP_NAME', 'dokpress')));

/**
 * Disables the built-in HTTP cron. The `wp-cron` container runs due events.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-cron-and-cron-timeout
 */
define('DISABLE_WP_CRON', true);

/**
 * Disables the plugin and theme file editor when `WP_BLOCK_UPDATE` is true.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-the-plugin-and-theme-file-editor
 */
define('DISALLOW_FILE_EDIT', Environment::get('WP_BLOCK_UPDATE'));

/**
 * Disables installing and updating plugins and themes from the admin when `WP_BLOCK_UPDATE` is true.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-plugin-and-theme-update-and-installation
 */
define('DISALLOW_FILE_MODS', Environment::get('WP_BLOCK_UPDATE'));

if(!Environment::get('WP_BLOCK_UPDATE')) {
    /**
     * Allows direct filesystem writes when updates are not blocked.
     *
     * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wordpress-upgrade-constants
     */
    define('FS_METHOD', 'direct');
}

/**
 * Requires HTTPS for the administration screens in production.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#require-ssl-for-admin-and-logins
 */
define('FORCE_SSL_ADMIN', $production);

/**
 * Blocks outbound HTTP requests except the hosts listed in `WP_ACCESSIBLE_HOSTS`.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#block-external-url-requests
 */
define('WP_HTTP_BLOCK_EXTERNAL', Environment::get('WP_HTTP_BLOCK_EXTERNAL'));
define('WP_ACCESSIBLE_HOSTS', Environment::get('WP_ACCESSIBLE_HOSTS'));

/**
 * Disables WordPress background updates when `WP_BLOCK_UPDATE` is true.
 *
 * Core auto-updates stay off regardless of that flag.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-wordpress-auto-updates
 */
define('AUTOMATIC_UPDATER_DISABLED', Environment::get('WP_BLOCK_UPDATE'));
define('WP_AUTO_UPDATE_CORE', false);

/** WP Rocket license key from `WP_ROCKET_KEY`. */
define('WP_ROCKET_KEY', Environment::get('WP_ROCKET_KEY'));

/** WP Rocket account email from `WP_ROCKET_EMAIL`. */
define('WP_ROCKET_EMAIL', Environment::get('WP_ROCKET_EMAIL'));

/**
 * Absolute path to the WordPress core directory.
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', WP_PROJECT_PATH . '/wp-core/');
}

/** Loads WordPress. */
require_once ABSPATH . 'wp-settings.php';
