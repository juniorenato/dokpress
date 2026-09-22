<?php

namespace App\Security;

use App\Service\Environment;
use Symfony\Component\HttpFoundation\Request;

class SafeLogin
{
    private $request;
    private string $safeLogin;
    private string $requestUri;

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

    public function registerQueryVar(array $vars): array
    {
        $vars[] = 'dokpress_login';
        return $vars;
    }

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

    public function changeLoginUrl(): string
    {
        return home_url($this->safeLogin);
    }

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

    public function adminLoginRedirect(): void
    {
        if (str_contains($this->requestUri, 'wp-admin')
            && !is_user_logged_in()
        ) {
            $this->set404();
        }
    }

    public function templateRedirect(): void
    {
        if (preg_match('#^/admin/?$#', $this->requestUri)) {
            $this->set404();
        }
    }

    private function set404()
    {
        global $wp_query;

        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        exit;
    }
}
