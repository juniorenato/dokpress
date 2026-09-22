<?php
/**
 * Plugin Name: Dokpress Bootstrap
 * Description: Applies Dokpress security headers and the optional custom login URL.
 */

new App\Security\Headers();
new App\Security\SafeLogin();
