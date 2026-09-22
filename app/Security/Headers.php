<?php

namespace App\Security;

use App\Config\Setup;
use App\Service\Environment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends optional HTTP security headers on public WordPress responses.
 *
 * Each header is emitted only when its `ENABLE_*` environment flag is true.
 * Admin, AJAX, login and the custom login query variable are skipped.
 * Construct this class from a must-use plugin; nothing in the template does it.
 */
class Headers
{
    /**
     * Registers the header callback on WordPress `send_headers`.
     */
    public function __construct()
    {
        add_action('send_headers', [$this, 'applySecurityHeaders']);
    }

    /**
     * Writes the enabled security headers to the current response.
     *
     * @return void
     */
    public function applySecurityHeaders()
    {
        if (is_admin()
            || wp_doing_ajax()
            || did_action('login_init')
            || get_query_var('dokpress_login')
        ) {
            return;
        }

        $response = new Response();

        if(Environment::production() && Environment::get('ENABLE_STRICT_TRANSPORT_SECURITY', true)) {
            $response->headers->set(
                'Strict-Transport-Security',
                Environment::get(
                    'STRICT_TRANSPORT_SECURITY',
                    'max-age=63072000; includeSubDomains; preload'
                )
            );
        }

        if(Environment::get('ENABLE_X_CONTENT_TYPE_OPTION', true)) {
            $response->headers->set(
                'X-Content-Type-Options',
                Environment::get(
                    'X_CONTENT_TYPE_OPTION',
                    'nosniff'
                )
            );
        }

        if(Environment::get('ENABLE_X_FRAME_OPTION', true)) {
            $response->headers->set(
                'X-Frame-Options',
                Environment::get(
                    'X_FRAME_OPTION',
                    'SAMEORIGIN'
                )
            );
        }

        if(Environment::get('ENABLE_X_XSS_PROTECTION', true)) {
            $response->headers->set(
                'X-XSS-Protection',
                Environment::get(
                    'X_XSS_PROTECTION',
                    '1; mode=block'
                )
            );
        }

        if(Environment::get('ENABLE_REFERRER_POLICY', true)) {
            $response->headers->set(
                'Referrer-Policy',
                Environment::get(
                    'REFERRER_POLICY',
                    'strict-origin-when-cross-origin'
                )
            );
        }

        if(Environment::get('ENABLE_PERMISSIONS_POLICY', true)) {
            $response->headers->set(
                'Permissions-Policy',
                Environment::get(
                    'PERMISSIONS_POLICY',
                    'geolocation=(), camera=(), microphone=()'
                )
            );
        }

        if(Environment::get('ENABLE_CROSS_ORIGIN_EMBEDDER_POLICY', false)) {
            $response->headers->set(
                'Cross-Origin-Embedder-Policy',
                Environment::get(
                    'CROSS_ORIGIN_EMBEDDER_POLICY',
                    'require-corp'
                )
            );
        }

        if(Environment::get('ENABLE_CROSS_ORIGIN_OPENER_POLICY', true)) {
            $response->headers->set(
                'Cross-Origin-Opener-Policy',
                Environment::get(
                    'CROSS_ORIGIN_OPENER_POLICY',
                    'same-origin'
                )
            );
        }

        if(Environment::get('ENABLE_CONTENT_SECURITY_POLICY', false)) {
            $response->headers->set(
                'Content-Security-Policy',
                Environment::get(
                    'CONTENT_SECURITY_POLICY',
                    Setup::CONTENT_SECURITY_POLICY()
                )
            );
        }

        foreach ($response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
    }
}
