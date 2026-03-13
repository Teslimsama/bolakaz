<?php

function app_request_is_secure(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwardedProto === 'https') {
        return true;
    }

    $frontEndHttps = strtolower((string) ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? ''));
    if ($frontEndHttps === 'on') {
        return true;
    }

    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }

    return false;
}

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = app_request_is_secure();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function app_get_csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function app_is_mutating_request(): bool
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
}

function app_is_same_origin_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return false;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        return is_string($originHost) && strcasecmp($originHost, $host) === 0;
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer !== '') {
        $refererHost = parse_url($referer, PHP_URL_HOST);
        return is_string($refererHost) && strcasecmp($refererHost, $host) === 0;
    }

    // If neither header is present, allow and rely on CSRF token validation for authenticated flows.
    return true;
}

function app_is_authenticated_session(): bool
{
    return !empty($_SESSION['user']) || !empty($_SESSION['admin']);
}

function app_csrf_token_from_request(): string
{
    if (isset($_POST['_csrf']) && is_string($_POST['_csrf'])) {
        return $_POST['_csrf'];
    }

    if (isset($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    return '';
}

function app_require_csrf_for_mutations(): void
{
    if (!app_is_mutating_request()) {
        return;
    }

    // Always enforce same-origin for mutating requests.
    if (!app_is_same_origin_request()) {
        http_response_code(403);
        exit('Forbidden request origin');
    }

    // Require CSRF token for authenticated sessions.
    if (app_is_authenticated_session()) {
        $token = app_csrf_token_from_request();
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        if (!is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            http_response_code(403);
            exit('Invalid CSRF token');
        }
    }
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_money')) {
    function app_money($amount): string
    {
        return "\u{20A6}" . number_format((float) $amount, 2);
    }
}

if (!function_exists('app_placeholder_image')) {
    function app_placeholder_image(): string
    {
        return 'images/storefront-placeholder.svg';
    }
}

if (!function_exists('app_image_url')) {
    function app_image_url($filename, string $baseDir = 'images'): string
    {
        $name = trim((string) $filename);
        if ($name === '') {
            return app_placeholder_image();
        }
        $relative = rtrim($baseDir, '/') . '/' . ltrim($name, '/');
        $absolute = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($absolute)) {
            return app_placeholder_image();
        }

        return $relative;
    }
}

if (!function_exists('app_create_reset_code')) {
    function app_create_reset_code(int $ttlSeconds = 3600): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + max(300, $ttlSeconds);
        return $token . '.' . $expiresAt;
    }
}

if (!function_exists('app_validate_reset_code')) {
    function app_validate_reset_code(string $storedCode, string $providedToken): bool
    {
        if ($storedCode === '' || $providedToken === '') {
            return false;
        }

        $parts = explode('.', $storedCode, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$storedToken, $expiresAtRaw] = $parts;
        $expiresAt = (int) $expiresAtRaw;
        if ($expiresAt <= time()) {
            return false;
        }

        return hash_equals($storedToken, $providedToken);
    }
}

