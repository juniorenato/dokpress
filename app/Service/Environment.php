<?php

namespace App\Service;

class Environment
{
    /**
     * -------------------------------------------------------------------------
     * Get an environment variable
     * -------------------------------------------------------------------------
     *
     * Missing or empty values use $default. Boolean-looking strings become bool.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = ''): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        if ($value === 'true' || $value === '(true)') {
            return true;
        }

        if ($value === 'false' || $value === '(false)') {
            return false;
        }

        return $value;
    }

    /**
     * -------------------------------------------------------------------------
     * Check if is production
     * -------------------------------------------------------------------------
     *
     * @return boolean
     */
    public static function production(): bool
    {
        return (self::get('APP_ENV') === 'production'
            || self::get('APP_ENV') === 'prod'
        );
    }

    /**
     * -------------------------------------------------------------------------
     * Check if is staging
     * -------------------------------------------------------------------------
     *
     * @return boolean
     */
    public static function staging(): bool
    {
        return self::get('APP_ENV') === 'staging';
    }

    /**
     * -------------------------------------------------------------------------
     * Check if is development or local
     * -------------------------------------------------------------------------
     *
     * @return boolean
     */
    public static function local(): bool
    {
        return (self::get('APP_ENV') === 'loc'
            || self::get('APP_ENV') === 'local'
            || self::get('APP_ENV') === 'dev'
            || self::get('APP_ENV') === 'development'
        );
    }

    /**
     * -------------------------------------------------------------------------
     * Check if is development or local
     * -------------------------------------------------------------------------
     *
     * @return boolean
     */
    public static function dev(): bool
    {
        return self::local();
    }

    /**
     * -------------------------------------------------------------------------
     * Check if is development or local
     * -------------------------------------------------------------------------
     *
     * @return boolean
     */
    public static function development(): bool
    {
        return self::local();
    }
}
