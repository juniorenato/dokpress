<?php

namespace App\Security;

use App\Config\Setup;
use App\Service\Environment;
use Symfony\Component\HttpFoundation\Response;

class Headers
{
    public function __construct()
    {
        if(!is_admin()
            && !wp_doing_ajax()
            && !did_action('login_init')
            && !get_query_var('dokpress_login')
        ) { add_action('send_headers', [$this, 'applySecurityHeaders']); }
    }

    public function applySecurityHeaders()
    {
        $response = new Response();

        if(Environment::get('ENABLE_STRICT_TRANSPORT_SECURITY', true)) {
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

        if(Environment::get('ENABLE_CROSS_ORIGIN_EMBEDDER_POLICY', true)) {
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

        // Set headers in the PHP response
        foreach ($response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
    }
}
