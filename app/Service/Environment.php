<?php

namespace App\Service;

/**
 * Reads environment variables and classifies the runtime.
 *
 * Boolean-looking strings from the environment are cast before they are
 * returned. `dev()` and `development()` are aliases of {@see self::local()}.
 */
class Environment
{
    /**
     * Returns the value of an environment variable.
     *
     * A missing or empty value falls back to `$default`. The strings `true`
     * and `(true)` become `true`; `false` and `(false)` become `false`.
     *
     * @param string $key     Environment variable name.
     * @param mixed  $default Value used when the variable is missing or empty.
     *
     * @return mixed Resolved value, or `$default`.
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
     * Reports whether the application runs in production.
     *
     * Accepts `APP_ENV=production` and `APP_ENV=prod`.
     *
     * @return bool `true` when `APP_ENV` is production.
     */
    public static function production(): bool
    {
        return (self::get('APP_ENV') === 'production'
            || self::get('APP_ENV') === 'prod'
        );
    }

    /**
     * Reports whether the application runs in staging.
     *
     * @return bool `true` when `APP_ENV` is `staging`.
     */
    public static function staging(): bool
    {
        return self::get('APP_ENV') === 'staging';
    }

    /**
     * Reports whether the application runs in a local or development environment.
     *
     * Accepts `APP_ENV` values `loc`, `local`, `dev` and `development`.
     *
     * @return bool `true` when `APP_ENV` is local or development.
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
     * Reports whether the application runs in a local or development environment.
     *
     * Alias of {@see self::local()}.
     *
     * @return bool `true` when {@see self::local()} is `true`.
     */
    public static function dev(): bool
    {
        return self::local();
    }

    /**
     * Reports whether the application runs in a local or development environment.
     *
     * Alias of {@see self::local()}.
     *
     * @return bool `true` when {@see self::local()} is `true`.
     */
    public static function development(): bool
    {
        return self::local();
    }
}
