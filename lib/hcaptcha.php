<?php

function app_env_value(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }

    return trim((string) $value);
}

function app_is_local_env(): bool
{
    return strtolower(app_env_value('APP_ENV')) === 'local';
}

function app_hcaptcha_site_key(): string
{
    return app_env_value('HCAPTCHA_SITE_KEY');
}

function app_hcaptcha_secret_key(): string
{
    return app_env_value('HCAPTCHA_SECRET_KEY');
}

function app_hcaptcha_enabled(): bool
{
    return !app_is_local_env() && app_hcaptcha_site_key() !== '';
}

function app_hcaptcha_has_server_config(): bool
{
    return app_hcaptcha_secret_key() !== '';
}

/**
 * @return array{
 *   success: bool,
 *   error?: string,
 *   errors?: array<int, mixed>,
 *   response?: array<string, mixed>
 * }
 */
function app_hcaptcha_verify(string $token): array
{
    $secret = app_hcaptcha_secret_key();
    $siteKey = app_hcaptcha_site_key();

    if ($secret === '') {
        return [
            'success' => false,
            'error' => 'missing_config',
        ];
    }

    if ($token === '') {
        return [
            'success' => false,
            'error' => 'missing_token',
        ];
    }

    $payload = [
        'secret' => $secret,
        'response' => $token,
        'remoteip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ];

    if ($siteKey !== '') {
        $payload['sitekey'] = $siteKey;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $responseBody = @file_get_contents('https://api.hcaptcha.com/siteverify', false, $context);
    $response = is_string($responseBody) ? json_decode($responseBody, true) : null;

    if (!is_array($response)) {
        return [
            'success' => false,
            'error' => 'invalid_response',
        ];
    }

    if (empty($response['success'])) {
        return [
            'success' => false,
            'error' => 'verification_failed',
            'errors' => is_array($response['error-codes'] ?? null) ? $response['error-codes'] : [],
            'response' => $response,
        ];
    }

    return [
        'success' => true,
        'response' => $response,
    ];
}
