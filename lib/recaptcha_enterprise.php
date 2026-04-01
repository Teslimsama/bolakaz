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

function app_recaptcha_enterprise_site_key(): string
{
    return app_env_value('RECAPTCHA_ENTERPRISE_SITE_KEY');
}

function app_recaptcha_enterprise_project_id(): string
{
    return app_env_value('RECAPTCHA_ENTERPRISE_PROJECT_ID');
}

function app_recaptcha_enterprise_api_key(): string
{
    return app_env_value('RECAPTCHA_ENTERPRISE_API_KEY');
}

function app_recaptcha_enterprise_min_score(): float
{
    $raw = app_env_value('RECAPTCHA_ENTERPRISE_MIN_SCORE');
    if ($raw === '' || !is_numeric($raw)) {
        return 0.0;
    }

    $score = (float) $raw;
    if ($score < 0.0) {
        return 0.0;
    }
    if ($score > 1.0) {
        return 1.0;
    }

    return $score;
}

function app_recaptcha_enterprise_monthly_limit(): int
{
    $raw = app_env_value('RECAPTCHA_ENTERPRISE_MONTHLY_LIMIT');
    if ($raw === '' || !ctype_digit($raw)) {
        return 9500;
    }

    $limit = (int) $raw;
    if ($limit < 0) {
        return 0;
    }

    return $limit;
}

function app_recaptcha_enterprise_enabled(): bool
{
    return !app_is_local_env() && app_recaptcha_enterprise_site_key() !== '';
}

function app_recaptcha_enterprise_has_server_config(): bool
{
    return app_recaptcha_enterprise_project_id() !== '' && app_recaptcha_enterprise_api_key() !== '';
}

function app_recaptcha_enterprise_usage_file_path(): string
{
    return dirname(__DIR__) . '/storage/tmp/recaptcha-enterprise-usage.json';
}

function app_recaptcha_enterprise_month_key(): string
{
    return gmdate('Y-m');
}

function app_recaptcha_enterprise_usage_default_data(): array
{
    return [
        'current_month' => app_recaptcha_enterprise_month_key(),
        'count' => 0,
    ];
}

function app_recaptcha_enterprise_usage_load_from_handle($handle): array
{
    rewind($handle);
    $raw = stream_get_contents($handle);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        $data = app_recaptcha_enterprise_usage_default_data();
    }

    $currentMonth = app_recaptcha_enterprise_month_key();
    if (($data['current_month'] ?? '') !== $currentMonth) {
        $data = [
            'current_month' => $currentMonth,
            'count' => 0,
        ];
    }

    if (!isset($data['count']) || !is_numeric($data['count'])) {
        $data['count'] = 0;
    }
    $data['count'] = max(0, (int) $data['count']);

    return $data;
}

function app_recaptcha_enterprise_usage_save_to_handle($handle, array $data): bool
{
    rewind($handle);
    if (!ftruncate($handle, 0)) {
        return false;
    }

    $written = fwrite($handle, json_encode($data, JSON_PRETTY_PRINT));
    if ($written === false) {
        return false;
    }

    fflush($handle);
    return true;
}

/**
 * @return array{allowed: bool, count: int, limit: int, month: string}
 */
function app_recaptcha_enterprise_reserve_assessment_slot(): array
{
    $limit = app_recaptcha_enterprise_monthly_limit();
    $path = app_recaptcha_enterprise_usage_file_path();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $handle = @fopen($path, 'c+');
    if (!is_resource($handle)) {
        return [
            'allowed' => false,
            'count' => $limit,
            'limit' => $limit,
            'month' => app_recaptcha_enterprise_month_key(),
        ];
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            return [
                'allowed' => false,
                'count' => $limit,
                'limit' => $limit,
                'month' => app_recaptcha_enterprise_month_key(),
            ];
        }

        $data = app_recaptcha_enterprise_usage_load_from_handle($handle);
        $count = (int) $data['count'];

        if ($count >= $limit) {
            return [
                'allowed' => false,
                'count' => $count,
                'limit' => $limit,
                'month' => (string) $data['current_month'],
            ];
        }

        $data['count'] = $count + 1;
        app_recaptcha_enterprise_usage_save_to_handle($handle, $data);

        return [
            'allowed' => true,
            'count' => (int) $data['count'],
            'limit' => $limit,
            'month' => (string) $data['current_month'],
        ];
    } finally {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}

/**
 * @return array{
 *   success: bool,
 *   error?: string,
 *   score?: float,
 *   reasons?: array<int, mixed>,
 *   response?: array<string, mixed>
 * }
 */
function app_recaptcha_enterprise_assess(string $token, string $expectedAction): array
{
    $siteKey = app_recaptcha_enterprise_site_key();
    $projectId = app_recaptcha_enterprise_project_id();
    $apiKey = app_recaptcha_enterprise_api_key();
    $expectedAction = strtoupper(trim($expectedAction));

    if ($siteKey === '' || $projectId === '' || $apiKey === '') {
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

    $reservation = app_recaptcha_enterprise_reserve_assessment_slot();
    if (empty($reservation['allowed'])) {
        return [
            'success' => false,
            'error' => 'monthly_limit_reached',
            'limit' => $reservation['limit'],
            'count' => $reservation['count'],
            'month' => $reservation['month'],
        ];
    }

    $payload = [
        'event' => [
            'token' => $token,
            'siteKey' => $siteKey,
            'expectedAction' => $expectedAction,
            'userIpAddress' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'userAgent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ],
    ];

    $url = 'https://recaptchaenterprise.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/assessments?key=' . rawurlencode($apiKey);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($payload),
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $responseBody = @file_get_contents($url, false, $context);
    $response = is_string($responseBody) ? json_decode($responseBody, true) : null;

    if (!is_array($response)) {
        return [
            'success' => false,
            'error' => 'invalid_response',
        ];
    }

    if (isset($response['error'])) {
        return [
            'success' => false,
            'error' => 'request_failed',
            'limit' => $reservation['limit'],
            'count' => $reservation['count'],
            'response' => $response,
        ];
    }

    $tokenProperties = is_array($response['tokenProperties'] ?? null) ? $response['tokenProperties'] : [];
    $action = strtoupper(trim((string) ($tokenProperties['action'] ?? '')));
    $valid = !empty($tokenProperties['valid']);

    if (!$valid) {
        return [
            'success' => false,
            'error' => 'invalid_token',
            'limit' => $reservation['limit'],
            'count' => $reservation['count'],
            'response' => $response,
        ];
    }

    if ($expectedAction !== '' && $action !== '' && $action !== $expectedAction) {
        return [
            'success' => false,
            'error' => 'action_mismatch',
            'limit' => $reservation['limit'],
            'count' => $reservation['count'],
            'response' => $response,
        ];
    }

    $riskAnalysis = is_array($response['riskAnalysis'] ?? null) ? $response['riskAnalysis'] : [];
    $score = isset($riskAnalysis['score']) && is_numeric($riskAnalysis['score']) ? (float) $riskAnalysis['score'] : 0.0;
    $minScore = app_recaptcha_enterprise_min_score();

    if ($score < $minScore) {
        return [
            'success' => false,
            'error' => 'low_score',
            'score' => $score,
            'limit' => $reservation['limit'],
            'count' => $reservation['count'],
            'reasons' => is_array($riskAnalysis['reasons'] ?? null) ? $riskAnalysis['reasons'] : [],
            'response' => $response,
        ];
    }

    return [
        'success' => true,
        'score' => $score,
        'limit' => $reservation['limit'],
        'count' => $reservation['count'],
        'reasons' => is_array($riskAnalysis['reasons'] ?? null) ? $riskAnalysis['reasons'] : [],
        'response' => $response,
    ];
}
