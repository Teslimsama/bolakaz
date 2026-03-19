<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/CreateDb.php';
require_once $root . '/security.php';

if (!function_exists('curl_init')) {
    fwrite(STDERR, "cURL extension is required to run smoke_admin.php\n");
    exit(1);
}

$options = [
    'base-url' => '',
    'admin-email' => '',
    'admin-password' => '',
    'admin-id' => '',
];

foreach ($argv as $arg) {
    foreach (array_keys($options) as $name) {
        $prefix = '--' . $name . '=';
        if (strpos($arg, $prefix) === 0) {
            $options[$name] = trim((string)substr($arg, strlen($prefix)));
        }
    }
}

$baseUrl = $options['base-url'];
if ($baseUrl === '') {
    $baseUrl = trim((string)($_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? getenv('APP_URL') ?? 'http://localhost/bolakaz'));
}
$baseUrl = rtrim($baseUrl, '/');

$adminEmail = $options['admin-email'];
if ($adminEmail === '') {
    $adminEmail = trim((string)($_ENV['ADMIN_EMAIL'] ?? $_SERVER['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?? ''));
}

$adminPassword = $options['admin-password'];
if ($adminPassword === '') {
    $adminPassword = trim((string)($_ENV['ADMIN_PASSWORD'] ?? $_SERVER['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?? ''));
}

$adminIdOption = trim((string)$options['admin-id']);
$adminId = ctype_digit($adminIdOption) ? (int)$adminIdOption : 0;

$cookieFile = tempnam(sys_get_temp_dir(), 'bolakaz-admin-smoke-');
if ($cookieFile === false) {
    fwrite(STDERR, "Unable to create temporary cookie jar\n");
    exit(1);
}

$seededSessionId = '';
$seededSessionName = 'PHPSESSID';

register_shutdown_function(static function () use (&$cookieFile, &$seededSessionId): void {
    if ($seededSessionId !== '') {
        $sessionPath = rtrim((string)session_save_path(), DIRECTORY_SEPARATOR);
        if ($sessionPath !== '') {
            $sessionFile = $sessionPath . DIRECTORY_SEPARATOR . 'sess_' . $seededSessionId;
            if (is_file($sessionFile)) {
                @unlink($sessionFile);
            }
        }
    }

    if (is_file($cookieFile)) {
        @unlink($cookieFile);
    }
});

$errorMarkers = [
    'Fatal error',
    'Parse error',
    'Undefined variable',
    'An unexpected error occurred',
    'Whoops\\',
];

function absolute_url(string $baseUrl, string $path, array $query = []): string
{
    $normalizedPath = trim($path);
    if ($normalizedPath === '') {
        $normalizedPath = '/';
    } elseif ($normalizedPath[0] !== '/') {
        $normalizedPath = '/' . $normalizedPath;
    }

    $url = $baseUrl . $normalizedPath;
    if (!empty($query)) {
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $separator . http_build_query($query);
    }

    return $url;
}

function request_web(
    string $baseUrl,
    string $cookieFile,
    string $method,
    string $path,
    array $data = [],
    array $headers = [],
    array $defaultHeaders = []
): array {
    $upperMethod = strtoupper($method);
    $url = absolute_url($baseUrl, $path, $upperMethod === 'GET' ? $data : []);
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Unable to initialize cURL');
    }

    $requestHeaders = [
        'Accept: text/html,application/json;q=0.9,*/*;q=0.8',
        'Origin: ' . $baseUrl,
        'Referer: ' . $baseUrl . '/',
    ];
    foreach ($defaultHeaders as $header) {
        $requestHeaders[] = $header;
    }
    foreach ($headers as $header) {
        $requestHeaders[] = $header;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_CUSTOMREQUEST => $upperMethod,
    ]);

    if ($upperMethod === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException($error);
    }

    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $rawHeaders = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    curl_close($ch);

    $parsedHeaders = [];
    foreach (preg_split("/\r\n|\n|\r/", trim($rawHeaders)) ?: [] as $line) {
        $pos = strpos($line, ':');
        if ($pos === false) {
            continue;
        }

        $name = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($name === '') {
            continue;
        }

        $parsedHeaders[strtolower($name)] = $value;
    }

    return [
        'url' => $url,
        'status' => $status,
        'headers' => $parsedHeaders,
        'body' => $body,
    ];
}

function body_has_error_marker(string $body, array $markers): ?string
{
    foreach ($markers as $marker) {
        if ($marker !== '' && stripos($body, $marker) !== false) {
            return $marker;
        }
    }

    return null;
}

function extract_csrf_token(string $body): string
{
    if (preg_match('/<meta\s+name="csrf-token"\s+content="([^"]*)"/i', $body, $matches)) {
        return html_entity_decode((string)$matches[1], ENT_QUOTES, 'UTF-8');
    }

    return '';
}

function response_json(array $response): array
{
    $decoded = json_decode((string)$response['body'], true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Response is not valid JSON');
    }

    return $decoded;
}

function sample_id(PDO $conn, string $sql): int
{
    try {
        $stmt = $conn->query($sql);
        $value = $stmt !== false ? $stmt->fetchColumn() : false;
        return is_numeric($value) ? (int)$value : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

function build_sample_ids(): array
{
    global $pdo;

    $conn = $pdo->open();
    try {
        return [
            'admin_id' => sample_id($conn, "SELECT id FROM users WHERE type = 1 AND status = 1 ORDER BY id ASC LIMIT 1"),
            'user_id' => sample_id($conn, "SELECT id FROM users WHERE type = 0 ORDER BY id ASC LIMIT 1"),
            'product_id' => sample_id($conn, "SELECT id FROM products ORDER BY id ASC LIMIT 1"),
            'category_id' => sample_id($conn, "SELECT id FROM category ORDER BY id ASC LIMIT 1"),
            'shipping_id' => sample_id($conn, "SELECT id FROM shippings ORDER BY id ASC LIMIT 1"),
            'coupon_id' => sample_id($conn, "SELECT id FROM coupons ORDER BY id ASC LIMIT 1"),
            'banner_id' => sample_id($conn, "SELECT id FROM banner ORDER BY id ASC LIMIT 1"),
            'ads_id' => sample_id($conn, "SELECT id FROM ads ORDER BY id ASC LIMIT 1"),
            'web_details_id' => sample_id($conn, "SELECT id FROM web_details ORDER BY id ASC LIMIT 1"),
            'sales_id' => sample_id($conn, "SELECT sales.id FROM sales INNER JOIN details ON details.sales_id = sales.id GROUP BY sales.id ORDER BY sales.id ASC LIMIT 1"),
            'offline_sales_id' => sample_id($conn, "SELECT sales.id FROM sales INNER JOIN details ON details.sales_id = sales.id WHERE sales.is_offline = 1 GROUP BY sales.id ORDER BY sales.id ASC LIMIT 1"),
        ];
    } finally {
        $pdo->close();
    }
}

function seed_admin_session(int $adminId): array
{
    global $seededSessionId, $seededSessionName;

    if ($adminId <= 0) {
        throw new RuntimeException('No active admin user was found for session seeding');
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $seededSessionId = bin2hex(random_bytes(16));
    session_id($seededSessionId);
    app_start_session();
    $_SESSION['admin'] = $adminId;
    app_get_csrf_token();
    session_write_close();

    $seededSessionName = session_name();

    return [
        'mode' => 'seed',
        'default_headers' => [
            'Cookie: ' . $seededSessionName . '=' . $seededSessionId,
        ],
        'detail' => 'Seeded admin session for user ID ' . $adminId,
    ];
}

function login_admin(string $baseUrl, string $cookieFile, string $email, string $password): array
{
    if ($email === '' || $password === '') {
        throw new RuntimeException('Admin email and password are required for login mode');
    }

    $response = request_web(
        $baseUrl,
        $cookieFile,
        'POST',
        'app/signin.app.php',
        ['email' => $email, 'password' => $password]
    );

    $location = (string)($response['headers']['location'] ?? '');
    if ($response['status'] !== 302 || stripos($location, 'admin/home') === false) {
        throw new RuntimeException('Admin login did not redirect to admin/home');
    }

    return [
        'mode' => 'login',
        'default_headers' => [],
        'detail' => 'Logged in as ' . $email,
    ];
}

function run_page_check(array $definition, string $baseUrl, string $cookieFile, array $defaultHeaders, array $errorMarkers): array
{
    $response = request_web(
        $baseUrl,
        $cookieFile,
        (string)$definition['method'],
        (string)$definition['path'],
        $definition['data'] ?? [],
        $definition['headers'] ?? [],
        $defaultHeaders
    );

    $allowedStatuses = $definition['statuses'] ?? [200];
    if (!in_array($response['status'], $allowedStatuses, true)) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => 'Unexpected status ' . $response['status'],
        ];
    }

    $marker = body_has_error_marker((string)$response['body'], $errorMarkers);
    if ($marker !== null) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => 'Response contains error marker: ' . $marker,
        ];
    }

    if (!empty($definition['body_contains']) && stripos((string)$response['body'], (string)$definition['body_contains']) === false) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => 'Expected body marker not found',
        ];
    }

    return [
        'ok' => true,
        'label' => $definition['label'],
        'detail' => 'HTTP ' . $response['status'],
    ];
}

function run_json_check(
    array $definition,
    string $baseUrl,
    string $cookieFile,
    array $defaultHeaders,
    array $errorMarkers,
    string $csrfToken,
    array $samples
): array {
    if (!empty($definition['sample_key'])) {
        $sampleKey = (string)$definition['sample_key'];
        if (empty($samples[$sampleKey])) {
            return [
                'skip' => true,
                'label' => $definition['label'],
                'detail' => 'No sample data available for ' . $sampleKey,
            ];
        }
    }

    $headers = ['Accept: application/json'];
    if (($definition['method'] ?? 'GET') !== 'GET' && $csrfToken !== '') {
        $headers[] = 'X-CSRF-Token: ' . $csrfToken;
    }
    foreach (($definition['headers'] ?? []) as $header) {
        $headers[] = $header;
    }

    $data = [];
    if (isset($definition['data'])) {
        $data = (array)$definition['data'];
    } elseif (isset($definition['data_builder']) && is_callable($definition['data_builder'])) {
        $data = (array)call_user_func($definition['data_builder'], $samples);
    }

    $response = request_web(
        $baseUrl,
        $cookieFile,
        (string)$definition['method'],
        (string)$definition['path'],
        $data,
        $headers,
        $defaultHeaders
    );

    $allowedStatuses = $definition['statuses'] ?? [200];
    if (!in_array($response['status'], $allowedStatuses, true)) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => 'Unexpected status ' . $response['status'],
        ];
    }

    $marker = body_has_error_marker((string)$response['body'], $errorMarkers);
    if ($marker !== null) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => 'Response contains error marker: ' . $marker,
        ];
    }

    try {
        $json = response_json($response);
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => $e->getMessage(),
        ];
    }

    if (!empty($definition['validator']) && is_callable($definition['validator'])) {
        $validation = call_user_func($definition['validator'], $json);
        if (is_string($validation) && $validation !== '') {
            return [
                'ok' => false,
                'label' => $definition['label'],
                'detail' => $validation,
            ];
        }
    }

    return [
        'ok' => true,
        'label' => $definition['label'],
        'detail' => 'HTTP ' . $response['status'],
    ];
}

$samples = build_sample_ids();

try {
    $auth = ($adminEmail !== '' || $adminPassword !== '')
        ? login_admin($baseUrl, $cookieFile, $adminEmail, $adminPassword)
        : seed_admin_session($adminId > 0 ? $adminId : (int)$samples['admin_id']);
} catch (Throwable $e) {
    fwrite(STDERR, '[FAIL] Admin authentication - ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo '[PASS] Admin authentication - ' . $auth['detail'] . PHP_EOL;

$bootstrap = request_web($baseUrl, $cookieFile, 'GET', 'admin/home', [], [], $auth['default_headers']);
if ($bootstrap['status'] !== 200) {
    fwrite(STDERR, '[FAIL] Admin bootstrap - Unexpected status ' . $bootstrap['status'] . PHP_EOL);
    exit(1);
}

$bootstrapMarker = body_has_error_marker((string)$bootstrap['body'], $errorMarkers);
if ($bootstrapMarker !== null) {
    fwrite(STDERR, '[FAIL] Admin bootstrap - Response contains error marker: ' . $bootstrapMarker . PHP_EOL);
    exit(1);
}

$csrfToken = extract_csrf_token((string)$bootstrap['body']);
if ($csrfToken === '') {
    fwrite(STDERR, '[FAIL] Admin bootstrap - Unable to extract CSRF token' . PHP_EOL);
    exit(1);
}

echo '[PASS] Admin bootstrap - Authenticated admin HTML and CSRF token loaded' . PHP_EOL;

$pageChecks = [
    ['label' => 'Admin home loads', 'method' => 'GET', 'path' => 'admin/home', 'statuses' => [200], 'body_contains' => 'Dashboard'],
    ['label' => 'Admin sales page loads', 'method' => 'GET', 'path' => 'admin/sales', 'statuses' => [200], 'body_contains' => 'Sales History'],
    ['label' => 'Admin offline sales page loads', 'method' => 'GET', 'path' => 'admin/offline_sales', 'statuses' => [200], 'body_contains' => 'Offline Sales History'],
    ['label' => 'Admin users page loads', 'method' => 'GET', 'path' => 'admin/users', 'statuses' => [200], 'body_contains' => 'Users'],
    ['label' => 'Admin products page loads', 'method' => 'GET', 'path' => 'admin/products', 'statuses' => [200], 'body_contains' => 'Product List'],
    ['label' => 'Admin category page loads', 'method' => 'GET', 'path' => 'admin/category', 'statuses' => [200], 'body_contains' => 'Category'],
    ['label' => 'Admin shipping page loads', 'method' => 'GET', 'path' => 'admin/shipping', 'statuses' => [200], 'body_contains' => 'Shipping Methods'],
    ['label' => 'Admin coupon page loads', 'method' => 'GET', 'path' => 'admin/coupon', 'statuses' => [200], 'body_contains' => 'Coupons'],
    ['label' => 'Admin banner page loads', 'method' => 'GET', 'path' => 'admin/banner', 'statuses' => [200], 'body_contains' => 'Banner'],
    ['label' => 'Admin ads page loads', 'method' => 'GET', 'path' => 'admin/ads', 'statuses' => [200], 'body_contains' => 'Ads'],
    ['label' => 'Admin web details page loads', 'method' => 'GET', 'path' => 'admin/web_details', 'statuses' => [200], 'body_contains' => 'Web Details'],
];

$jsonChecks = [
    [
        'label' => 'Dashboard metrics endpoint responds',
        'method' => 'GET',
        'path' => 'admin/dashboard_metrics',
        'data' => [
            'year' => date('Y'),
            'start_date' => date('Y-01-01'),
            'end_date' => date('Y-12-31'),
        ],
        'validator' => static function (array $json): string {
            if (empty($json['success'])) {
                return 'Dashboard metrics returned success=false';
            }
            if (!isset($json['cards']) || !is_array($json['cards'])) {
                return 'Dashboard metrics cards payload is missing';
            }
            return '';
        },
    ],
    [
        'label' => 'Category fetch endpoint responds',
        'method' => 'POST',
        'path' => 'admin/category_fetch.php',
        'validator' => static function (array $json): string {
            if (empty($json['success'])) {
                return 'Category fetch returned success=false';
            }
            if (!array_key_exists('count', $json)) {
                return 'Category fetch count is missing';
            }
            return '';
        },
    ],
    [
        'label' => 'User row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/users_row.php',
        'sample_key' => 'user_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['user_id']];
        },
        'validator' => static function (array $json): string {
            if (!empty($json['error'])) {
                return 'User row returned error=true';
            }
            if (empty($json['id']) || empty($json['email'])) {
                return 'User row payload is incomplete';
            }
            return '';
        },
    ],
    [
        'label' => 'Product row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/products_row.php',
        'sample_key' => 'product_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['product_id']];
        },
        'validator' => static function (array $json): string {
            if (empty($json['prodid']) && empty($json['id'])) {
                return 'Product row payload is missing product ID';
            }
            if (empty($json['prodname']) && empty($json['name'])) {
                return 'Product row payload is missing product name';
            }
            return '';
        },
    ],
    [
        'label' => 'Category row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/category_row.php',
        'sample_key' => 'category_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['category_id']];
        },
        'validator' => static function (array $json): string {
            if (empty($json['success'])) {
                return 'Category row returned success=false';
            }
            if (empty($json['category']['id'])) {
                return 'Category row payload is missing category data';
            }
            return '';
        },
    ],
    [
        'label' => 'Shipping row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/shipping_row.php',
        'sample_key' => 'shipping_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['shipping_id']];
        },
        'validator' => static function (array $json): string {
            if (!empty($json['error'])) {
                return 'Shipping row returned error=true';
            }
            if (empty($json['id'])) {
                return 'Shipping row payload is missing ID';
            }
            return '';
        },
    ],
    [
        'label' => 'Coupon row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/coupon_row.php',
        'sample_key' => 'coupon_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['coupon_id']];
        },
        'validator' => static function (array $json): string {
            if (!empty($json['error'])) {
                return 'Coupon row returned error=true';
            }
            if (empty($json['id'])) {
                return 'Coupon row payload is missing ID';
            }
            return '';
        },
    ],
    [
        'label' => 'Banner row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/banner_row.php',
        'sample_key' => 'banner_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['banner_id']];
        },
        'validator' => static function (array $json): string {
            if (!empty($json['error'])) {
                return 'Banner row returned error=true';
            }
            if (empty($json['id'])) {
                return 'Banner row payload is missing ID';
            }
            return '';
        },
    ],
    [
        'label' => 'Ads row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/ads_row.php',
        'sample_key' => 'ads_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['ads_id']];
        },
        'validator' => static function (array $json): string {
            if (!empty($json['error'])) {
                return 'Ads row returned error=true';
            }
            if (empty($json['id'])) {
                return 'Ads row payload is missing ID';
            }
            return '';
        },
    ],
    [
        'label' => 'Web details row endpoint responds',
        'method' => 'POST',
        'path' => 'admin/web_details_row.php',
        'sample_key' => 'web_details_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['web_details_id']];
        },
        'validator' => static function (array $json): string {
            if (!empty($json['error'])) {
                return 'Web details row returned error=true';
            }
            if (empty($json['id'])) {
                return 'Web details row payload is missing ID';
            }
            return '';
        },
    ],
    [
        'label' => 'Sales transact endpoint responds',
        'method' => 'POST',
        'path' => 'admin/transact.php',
        'sample_key' => 'sales_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['sales_id']];
        },
        'validator' => static function (array $json): string {
            if (!array_key_exists('transaction', $json) || !array_key_exists('total', $json)) {
                return 'Sales transact payload is incomplete';
            }
            return '';
        },
    ],
    [
        'label' => 'Offline sale details endpoint responds',
        'method' => 'POST',
        'path' => 'admin/offline_sales_details.php',
        'sample_key' => 'offline_sales_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['offline_sales_id']];
        },
        'validator' => static function (array $json): string {
            if (!array_key_exists('customer', $json) || !array_key_exists('total', $json)) {
                return 'Offline sale details payload is incomplete';
            }
            return '';
        },
    ],
    [
        'label' => 'Offline payments endpoint responds',
        'method' => 'POST',
        'path' => 'admin/offline_payments_row.php',
        'sample_key' => 'offline_sales_id',
        'data_builder' => static function (array $samples): array {
            return ['id' => $samples['offline_sales_id']];
        },
        'validator' => static function (array $json): string {
            if (!array_key_exists('id', $json) || !array_key_exists('balance_formatted', $json)) {
                return 'Offline payments payload is incomplete';
            }
            return '';
        },
    ],
];

$failures = 0;

foreach ($pageChecks as $check) {
    $result = run_page_check($check, $baseUrl, $cookieFile, $auth['default_headers'], $errorMarkers);
    $prefix = $result['ok'] ? '[PASS]' : '[FAIL]';
    echo $prefix . ' ' . $result['label'] . ' - ' . $result['detail'] . PHP_EOL;
    if (!$result['ok']) {
        $failures++;
    }
}

foreach ($jsonChecks as $check) {
    $result = run_json_check($check, $baseUrl, $cookieFile, $auth['default_headers'], $errorMarkers, $csrfToken, $samples);
    if (!empty($result['skip'])) {
        echo '[SKIP] ' . $result['label'] . ' - ' . $result['detail'] . PHP_EOL;
        continue;
    }

    $prefix = $result['ok'] ? '[PASS]' : '[FAIL]';
    echo $prefix . ' ' . $result['label'] . ' - ' . $result['detail'] . PHP_EOL;
    if (!$result['ok']) {
        $failures++;
    }
}

if ($failures > 0) {
    echo PHP_EOL . 'Admin smoke checks failed: ' . $failures . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'Admin smoke checks passed.' . PHP_EOL;
