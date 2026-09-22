<?php

namespace App\Config;

/**
 * Default application settings that are not read from the environment.
 */
class Setup
{
    /**
     * Builds the default Content-Security-Policy header value.
     *
     * Used by {@see \App\Security\Headers} when `CONTENT_SECURITY_POLICY` is empty
     * and `ENABLE_CONTENT_SECURITY_POLICY` is enabled.
     *
     * @return string Policy directives separated by semicolons.
     */
    public static function CONTENT_SECURITY_POLICY(): string
    {
        $data = [
            'default-src' => [
                "'self'",
            ],
            'script-src' => [
                "'self'",
                'https://cdn.jsdelivr.net',
            ],
            'style-src' => [
                "'self'",
                'https://fonts.googleapis.com',
            ],
            'font-src' => [
                "'self'",
                'https://fonts.gstatic.com',
            ],
            'img-src' => [
                "'self'",
                'data:',
            ],
        ];

        $csp = '';
        foreach($data as $k => $v) {
            $csp .= $k . ' ' . implode(' ', $v) . '; ';
        }

        return  $csp;
    }
}
