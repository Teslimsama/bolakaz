<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/CreateDb.php';

if (!function_exists('curl_init')) {
    fwrite(STDERR, "cURL extension is required to run smoke_web.php\n");
    exit(1);
}

$baseUrl = '';
foreach ($argv as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $baseUrl = trim((string)substr($arg, 11));
        break;
    }
}

if ($baseUrl === '') {
    $baseUrl = trim((string)($_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? getenv('APP_URL') ?? 'http://localhost/bolakaz'));
}
$baseUrl = rtrim($baseUrl, '/');

$productSlug = '';
try {
    $conn = $pdo->open();
    $stmt = $conn->query("SELECT slug FROM products WHERE product_status = 1 AND slug <> '' ORDER BY id ASC LIMIT 1");
    $productSlug = trim((string)$stmt->fetchColumn());
} catch (Throwable $e) {
    $productSlug = '';
}
$pdo->close();

$cookieFile = tempnam(sys_get_temp_dir(), 'bolakaz-smoke-');
if ($cookieFile === false) {
    fwrite(STDERR, "Unable to create temporary cookie jar\n");
    exit(1);
}

register_shutdown_function(static function () use ($cookieFile): void {
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

function absolute_url(string $baseUrl, string $path): string
{
    if ($path === '') {
        return $baseUrl . '/';
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return $baseUrl . $path;
}

function request_web(string $baseUrl, string $cookieFile, string $method, string $path, array $data = [], array $headers = []): array
{
    $url = absolute_url($baseUrl, $path);
    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Unable to initialize cURL');
    }

    $requestHeaders = [
        'Accept: text/html,application/json;q=0.9,*/*;q=0.8',
        'Origin: ' . $baseUrl,
        'Referer: ' . $baseUrl . '/',
    ];
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
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
    ]);

    if (strtoupper($method) === 'POST') {
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

function run_check(array $definition, string $baseUrl, string $cookieFile, array $errorMarkers): array
{
    $response = request_web(
        $baseUrl,
        $cookieFile,
        (string)$definition['method'],
        (string)$definition['path'],
        $definition['data'] ?? [],
        $definition['headers'] ?? [],
    );

    $allowedStatuses = $definition['statuses'] ?? [200];
    if (!in_array($response['status'], $allowedStatuses, true)) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => 'Unexpected status ' . $response['status'],
        ];
    }

    $marker = body_has_error_marker($response['body'], $errorMarkers);
    if ($marker !== null) {
        return [
            'ok' => false,
            'label' => $definition['label'],
            'detail' => 'Response contains error marker: ' . $marker,
        ];
    }

    if (!empty($definition['location_contains'])) {
        $location = (string)($response['headers']['location'] ?? '');
        if ($location === '' || stripos($location, (string)$definition['location_contains']) === false) {
            return [
                'ok' => false,
                'label' => $definition['label'],
                'detail' => 'Redirect location mismatch',
            ];
        }
    }

    if (!empty($definition['body_contains']) && stripos($response['body'], (string)$definition['body_contains']) === false) {
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

$checks = [
    [
        'label' => 'Storefront home loads',
        'method' => 'GET',
        'path' => '',
        'statuses' => [200],
        'body_contains' => 'Bolakaz',
    ],
    [
        'label' => 'Storefront cart loads for guest',
        'method' => 'GET',
        'path' => 'cart',
        'statuses' => [200],
        'body_contains' => 'Shopping Cart',
    ],
    [
        'label' => 'Storefront checkout loads for guest',
        'method' => 'GET',
        'path' => 'checkout',
        'statuses' => [200],
        'body_contains' => 'Checkout',
    ],
    [
        'label' => 'Profile redirects when guest',
        'method' => 'GET',
        'path' => 'profile',
        'statuses' => [302],
        'location_contains' => 'index',
    ],
    [
        'label' => 'Contact page loads',
        'method' => 'GET',
        'path' => 'contact',
        'statuses' => [200],
        'body_contains' => 'Contact',
    ],
    [
        'label' => 'Cart details endpoint responds',
        'method' => 'POST',
        'path' => 'cart_details',
        'statuses' => [200],
    ],
    [
        'label' => 'Cart mini endpoint responds',
        'method' => 'POST',
        'path' => 'cart_fetch',
        'statuses' => [200],
    ],
    [
        'label' => 'Cart total endpoint responds',
        'method' => 'POST',
        'path' => 'cart_total',
        'statuses' => [200],
    ],
    [
        'label' => 'Signin handler rejects empty payload cleanly',
        'method' => 'POST',
        'path' => 'app/signin.app.php',
        'data' => ['email' => '', 'password' => ''],
        'statuses' => [302],
        'location_contains' => 'signin',
    ],
    [
        'label' => 'Signup handler rejects incomplete payload cleanly',
        'method' => 'POST',
        'path' => 'app/signup.app.php',
        'data' => [
            'firstname' => 'Smoke',
            'lastname' => 'Test',
            'email' => 'smoke@example.com',
            'phone' => '',
            'gender' => '',
            'password' => '',
            'repassword' => '',
        ],
        'statuses' => [302],
        'location_contains' => 'signup',
    ],
    [
        'label' => 'Coupon handler rejects invalid code cleanly',
        'method' => 'POST',
        'path' => 'coupon',
        'data' => ['coupon_code' => 'INVALID-SMOKE-CODE'],
        'statuses' => [302],
        'location_contains' => 'cart',
    ],
    [
        'label' => 'Newsletter handler rejects invalid email cleanly',
        'method' => 'POST',
        'path' => 'newletter.php',
        'data' => ['name' => 'Smoke Test', 'email' => 'invalid-email'],
        'statuses' => [302],
    ],
    [
        'label' => 'Admin home guard redirects when guest',
        'method' => 'GET',
        'path' => 'admin/home',
        'statuses' => [302],
        'location_contains' => 'index',
    ],
    [
        'label' => 'Admin users guard redirects when guest',
        'method' => 'GET',
        'path' => 'admin/users',
        'statuses' => [302],
        'location_contains' => 'index',
    ],
];

if ($productSlug !== '') {
    $checks[] = [
        'label' => 'Product detail page loads',
        'method' => 'GET',
        'path' => 'detail?product=' . rawurlencode($productSlug),
        'statuses' => [200],
        'body_contains' => 'Shop Detail',
    ];
}

$failures = 0;
foreach ($checks as $check) {
    $result = run_check($check, $baseUrl, $cookieFile, $errorMarkers);
    $prefix = $result['ok'] ? '[PASS]' : '[FAIL]';
    echo $prefix . ' ' . $result['label'] . ' - ' . $result['detail'] . PHP_EOL;
    if (!$result['ok']) {
        $failures++;
    }
}

if ($failures > 0) {
    echo PHP_EOL . 'Smoke checks failed: ' . $failures . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'Smoke checks passed.' . PHP_EOL;
