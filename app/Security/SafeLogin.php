<?php

namespace App\Security;

use App\Service\Environment;
use Symfony\Component\HttpFoundation\Request;

/**
 * Hides `wp-login.php` behind the path configured in `WP_LOGIN_URL`.
 *
 * When `WP_LOGIN_URL` is empty, the constructor registers no WordPress hooks.
 * Direct requests to `wp-login.php`, `wp-admin` (anonymous) and `/admin`
 * end as a 404. Construct this class from a must-use plugin.
 */
class SafeLogin
{
    /**
     * Current HTTP request.
     *
     * @var Request
     */
    private $request;

    /**
     * Login path without a leading slash.
     */
    private string $safeLogin;

    /**
     * Request URI, including the query string when present.
     */
    private string $requestUri;

    /**
     * Reads `WP_LOGIN_URL` and registers the login hooks when it is set.
     */
    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        $this->requestUri = $this->request->getRequestUri();

        $safeLogin = Environment::get('WP_LOGIN_URL', '');

        if ($safeLogin) {
            $this->safeLogin = ltrim((string) $safeLogin, '/');

            add_filter('query_vars', [$this, 'registerQueryVar']);
            add_action('init', [$this, 'loginUrl']);
            add_filter('login_url', [$this, 'changeLoginUrl']);
            add_action('init', [$this, 'adminLoginRedirect']);
            add_action('template_redirect', [$this, 'templateRedirect']);
            add_action('init', [$this, 'loadCustomLogin'], 1);
        }
    }

    /**
     * Adds the `dokpress_login` query variable.
     *
     * @param array<int, string> $vars Query variables already registered by WordPress.
     *
     * @return array<int, string> Query variables, including `dokpress_login`.
     */
    public function registerQueryVar(array $vars): array
    {
        $vars[] = 'dokpress_login';
        return $vars;
    }

    /**
     * Maps the safe login path to `dokpress_login` and blocks direct login access.
     *
     * Anonymous `GET` requests whose URI contains `wp-login.php` receive a 404.
     *
     * @return void
     */
    public function loginUrl(): void
    {
        add_rewrite_rule(
            '^' . preg_quote($this->safeLogin, '#') . '/?$',
            'index.php?dokpress_login=1',
            'top'
        );

        if ($this->request->isMethod('GET')
            && strpos($this->requestUri, 'wp-login.php') !== false
            && !is_user_logged_in()
        ) {
            $this->set404();
        }
    }

    /**
     * Replaces the WordPress login URL with the safe path.
     *
     * @return string Home URL joined with the configured login path.
     */
    public function changeLoginUrl(): string
    {
        return home_url($this->safeLogin);
    }

    /**
     * Loads `wp-login.php` when the request matches the safe login path.
     *
     * The match is either the request path or the `dokpress_login` query variable.
     * The script exits after WordPress renders the login screen.
     *
     * @return void
     */
    public function loadCustomLogin(): void
    {
        $path = trim((string) parse_url($this->requestUri, PHP_URL_PATH), '/');
        $isCustomPath = $path === $this->safeLogin;
        $isQueryVar = isset($GLOBALS['wp_query']) && $GLOBALS['wp_query'] instanceof \WP_Query
            && (int) get_query_var('dokpress_login') === 1;

        if (!$isCustomPath && !$isQueryVar) {
            return;
        }

        require ABSPATH . 'wp-login.php';
        exit;
    }

    /**
     * Returns 404 when an anonymous request targets `wp-admin`.
     *
     * @return void
     */
    public function adminLoginRedirect(): void
    {
        if (str_contains($this->requestUri, 'wp-admin')
            && !is_user_logged_in()
        ) {
            $this->set404();
        }
    }

    /**
     * Returns 404 for requests to `/admin`.
     *
     * @return void
     */
    public function templateRedirect(): void
    {
        if (preg_match('#^/admin/?$#', $this->requestUri)) {
            $this->set404();
        }
    }

    /**
     * Ends the request with a WordPress 404 and no cache headers.
     *
     * @return never
     */
    private function set404()
    {
        global $wp_query;

        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        exit;
    }
}
