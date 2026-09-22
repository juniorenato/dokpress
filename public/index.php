<?php

/**
 * Front controller for the WordPress application.
 *
 * Loads `wp-core/wp-blog-header.php`, which bootstraps WordPress and renders the theme.
 */

/**
 * Tells WordPress to load the active theme and print the response.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

/** Loads the WordPress environment and template. */
require __DIR__ . '/wp-core/wp-blog-header.php';
