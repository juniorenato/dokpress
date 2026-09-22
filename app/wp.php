<?php

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 *
 */

use App\Service\Environment;

if(!defined('WD_BASE_PATH')) define('WD_BASE_PATH', realpath(__DIR__ . '/../..'));
if(!defined('WP_PROJECT_PATH')) define('WP_PROJECT_PATH', WD_BASE_PATH .'/public');

require_once WD_BASE_PATH .'/app/bootstrap.php';

// Detect HTTPS through reverse proxy (Traefik/Dokploy)
// Required to avoid ERR_TOO_MANY_REDIRECTS when FORCE_SSL_ADMIN is enabled
if (
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
) {
    $_SERVER['HTTPS'] = 'on';
}

$site_url = Environment::get('APP_URL');

$production = Environment::production();
$debug = (bool) Environment::get('WP_DEBUG', false);

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', Environment::get('DB_NAME'));

/** Database username */
define('DB_USER', Environment::get('DB_USER'));

/** Database password */
define('DB_PASSWORD', Environment::get('DB_PASSWORD'));

/** Database hostname */
define('DB_HOST', Environment::get('DB_HOST'));

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', Environment::get('DB_CHARSET'));

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', Environment::get('DB_COLLATE'));

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
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
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = Environment::get('DB_TABLE_PREFIX');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-debug
 */
define('WP_DEBUG', $debug);

/* Add any custom values between this line and the "stop editing" line. */

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#blog-address-url
define('WP_HOME', $site_url);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-siteurl
$site_url = trim($site_url, '/');
define('WP_SITEURL', $site_url .'/wp-core');

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#moving-wp-content-folder
define('WP_CONTENT_DIR', WP_PROJECT_PATH . '/wp-content');
define('WP_CONTENT_URL', $site_url .'/wp-content');

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#post-revisions
define('WP_POST_REVISIONS', 3);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#set-cookie-domain
define('COOKIE_DOMAIN', Environment::get('APP_DOMAIN'));

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#configure-error-logging
$logfile = WD_BASE_PATH .'/app/Log/wp-debug-'. date('Y-m') .'.log';
define('WP_DEBUG_LOG', $logfile);

// https://wordpress.org/support/article/debugging-in-wordpress/#wp_debug_display
// Enabled only when WP_DEBUG is on in non-production environments and WP_DEBUG_LOG is off, otherwise check debug.log file.
define('WP_DEBUG_DISPLAY', $debug && !$production);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-disable-fatal-error-handler
define('WP_DISABLE_FATAL_ERROR_HANDLER', !$production);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-environment-type
$envType = Environment::get('APP_ENV', 'development');
if ($envType === 'prod') {
    $envType = 'production';
} elseif (in_array($envType, ['dev', 'loc', 'local'], true)) {
    $envType = 'development';
}
define('WP_ENVIRONMENT_TYPE', $envType);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#script-debug
define('SCRIPT_DEBUG', $debug);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#cache
define('WP_CACHE', true);
define('WP_REDIS_HOST', Environment::get('REDIS_HOST', 'redis'));
define('WP_REDIS_PORT', Environment::get('REDIS_PORT', '6379'));
define('WP_REDIS_PREFIX', Environment::get('REDIS_PREFIX', Environment::get('APP_NAME', 'dokpress')));

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-cron-and-cron-timeout
define('DISABLE_WP_CRON', true);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-the-plugin-and-theme-file-editor
define('DISALLOW_FILE_EDIT', Environment::get('WP_BLOCK_UPDATE'));

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-plugin-and-theme-update-and-installation
define('DISALLOW_FILE_MODS', Environment::get('WP_BLOCK_UPDATE'));

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wordpress-upgrade-constants
if(!Environment::get('WP_BLOCK_UPDATE')) define('FS_METHOD', 'direct');

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#require-ssl-for-admin-and-logins
define('FORCE_SSL_ADMIN', $production);

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#block-external-url-requests
define('WP_HTTP_BLOCK_EXTERNAL', Environment::get('WP_HTTP_BLOCK_EXTERNAL'));
define('WP_ACCESSIBLE_HOSTS', Environment::get('WP_ACCESSIBLE_HOSTS'));

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-wordpress-auto-updates
define('AUTOMATIC_UPDATER_DISABLED', Environment::get('WP_BLOCK_UPDATE'));

// https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#disable-wordpress-core-updates
define('WP_AUTO_UPDATE_CORE', false);

// WP ROCKET LICENSE
define('WP_ROCKET_KEY', Environment::get('WP_ROCKET_KEY'));
define('WP_ROCKET_EMAIL', Environment::get('WP_ROCKET_EMAIL'));

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', WP_PROJECT_PATH . '/wp-core/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
