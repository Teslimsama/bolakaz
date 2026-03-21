<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/CreateDb.php';
require_once $root . '/security.php';

if (!function_exists('curl_init')) {
    fwrite(STDERR, "cURL extension is required to run destructive_flows.php\n");
    exit(1);
}

$options = [
    'base-url' => '',
    'allow-destructive' => '',
    'allow-remote' => '',
];

foreach ($argv as $arg) {
    foreach (array_keys($options) as $name) {
        $prefix = '--' . $name . '=';
        if (strpos($arg, $prefix) === 0) {
            $options[$name] = trim((string)substr($arg, strlen($prefix)));
        }
    }
}

function option_is_enabled(string $value): bool
{
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

$baseUrl = $options['base-url'];
if ($baseUrl === '') {
    $baseUrl = trim((string)($_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? getenv('APP_URL') ?? 'http://localhost/bolakaz'));
}
$baseUrl = rtrim($baseUrl, '/');

$allowDestructive = option_is_enabled($options['allow-destructive']);
$allowRemote = option_is_enabled($options['allow-remote']);

function base_url_host(string $baseUrl): string
{
    return strtolower(trim((string)(parse_url($baseUrl, PHP_URL_HOST) ?? '')));
}

function base_url_is_local(string $baseUrl): bool
{
    return in_array(base_url_host($baseUrl), ['localhost', '127.0.0.1'], true);
}

if (!$allowDestructive) {
    fwrite(STDERR, "[FAIL] Safety check - destructive flows are disabled by default. Re-run with --allow-destructive=1\n");
    exit(1);
}

if (!base_url_is_local($baseUrl) && !$allowRemote) {
    fwrite(STDERR, "[FAIL] Safety check - this destructive suite only runs against localhost by default. If you really want to target a remote host, re-run with --allow-remote=1\n");
    exit(1);
}

$errorMarkers = [
    'Fatal error',
    'Parse error',
    'Undefined variable',
    'An unexpected error occurred',
    'Whoops\\',
];

$cleanup = [
    'temp_files' => [],
    'session_ids' => [],
    'category_ids' => [],
    'shipping_ids' => [],
    'coupon_ids' => [],
    'offline_sale_ids' => [],
    'temp_user_ids' => [],
    'bank_sale' => [
        'sale_id' => 0,
        'product_id' => 0,
        'quantity' => 0,
        'confirmed' => false,
    ],
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

function make_cookie_file(string $prefix): string
{
    $file = tempnam(sys_get_temp_dir(), $prefix);
    if ($file === false) {
        throw new RuntimeException('Unable to create temporary cookie jar');
    }

    return $file;
}

function request_web(
    string $baseUrl,
    string $cookieFile,
    string $method,
    string $path,
    array $data = [],
    array $headers = [],
    array $defaultHeaders = [],
    array $files = []
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
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_CUSTOMREQUEST => $upperMethod,
    ]);

    if ($upperMethod === 'POST') {
        if (!empty($files)) {
            $postFields = $data;
            foreach ($files as $field => $filePath) {
                $filename = basename($filePath);
                if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
                    $filename .= '.png';
                }
                $postFields[$field] = curl_file_create($filePath, 'image/png', $filename);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
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

function extract_admin_alert_text(string $body): string
{
    if ($body === '') {
        return '';
    }

    if (preg_match("/<div class='alert alert-danger alert-dismissible'>.*?<h4><i class='icon fa fa-warning'><\\/i> Error!<\\/h4>(.*?)<\\/div>/si", $body, $matches)) {
        return trim((string)strip_tags($matches[1]));
    }

    if (preg_match("/<div class='alert alert-success alert-dismissible'>.*?<h4><i class='icon fa fa-check'><\\/i> Success!<\\/h4>(.*?)<\\/div>/si", $body, $matches)) {
        return trim((string)strip_tags($matches[1]));
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

function assert_redirect(array $response, string $contains = ''): void
{
    if (!in_array((int)$response['status'], [302, 303], true)) {
        throw new RuntimeException('Expected redirect, got HTTP ' . $response['status']);
    }

    if ($contains !== '') {
        $location = (string)($response['headers']['location'] ?? '');
        if ($location === '' || stripos($location, $contains) === false) {
            throw new RuntimeException('Redirect location mismatch');
        }
    }
}

function assert_no_runtime_errors(array $response, array $markers): void
{
    $marker = body_has_error_marker((string)$response['body'], $markers);
    if ($marker !== null) {
        throw new RuntimeException('Response contains runtime marker: ' . $marker);
    }
}

function record_result(bool $ok, string $label, string $detail, int &$failures): void
{
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . ' - ' . $detail . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}

function create_temp_png(array &$cleanup): string
{
    $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4////fwAJ+wP+f8nK8QAAAABJRU5ErkJggg==', true);
    if ($data === false) {
        throw new RuntimeException('Unable to decode temporary PNG data');
    }

    $file = tempnam(sys_get_temp_dir(), 'bolakaz-cat-');
    if ($file === false) {
        throw new RuntimeException('Unable to create temporary PNG file');
    }

    $pngFile = $file . '.png';
    if (!@rename($file, $pngFile)) {
        @unlink($file);
        throw new RuntimeException('Unable to prepare temporary PNG filename');
    }

    file_put_contents($pngFile, $data);
    $cleanup['temp_files'][] = $pngFile;
    return $pngFile;
}

function remove_cleanup_id(array &$bucket, int $id): void
{
    $bucket = array_values(array_filter($bucket, static function ($value) use ($id) {
        return (int)$value !== $id;
    }));
}

function seed_session(string $key, int $userId, array &$cleanup): array
{
    if ($userId <= 0) {
        throw new RuntimeException('Cannot seed session for an invalid user ID');
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $sessionId = bin2hex(random_bytes(16));
    session_id($sessionId);
    app_start_session();
    $_SESSION = [];
    $_SESSION[$key] = $userId;
    $csrfToken = app_get_csrf_token();
    session_write_close();

    $cleanup['session_ids'][] = $sessionId;

    return [
        'session_id' => $sessionId,
        'session_name' => session_name(),
        'csrf' => $csrfToken,
        'default_headers' => [
            'Cookie: ' . session_name() . '=' . $sessionId,
        ],
    ];
}

function csv_first_token(string $value): string
{
    $parts = array_values(array_filter(array_map('trim', explode(',', $value)), static function ($item) {
        return $item !== '';
    }));

    return $parts[0] ?? '';
}

function find_active_admin_id(PDO $conn): int
{
    $stmt = $conn->query("SELECT id FROM users WHERE type = 1 AND status = 1 ORDER BY id ASC LIMIT 1");
    $value = $stmt !== false ? $stmt->fetchColumn() : false;
    return is_numeric($value) ? (int)$value : 0;
}

function find_checkout_product(PDO $conn): array
{
    $stmt = $conn->query("SELECT id, name, qty, price, size, color FROM products WHERE product_status = 1 AND qty > 0 ORDER BY id ASC LIMIT 1");
    $row = $stmt !== false ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    if (!is_array($row)) {
        throw new RuntimeException('No active in-stock product was found for destructive flows');
    }

    return [
        'id' => (int)$row['id'],
        'name' => (string)($row['name'] ?? ''),
        'qty' => (int)($row['qty'] ?? 0),
        'price' => (float)($row['price'] ?? 0),
        'size' => csv_first_token((string)($row['size'] ?? '')),
        'color' => csv_first_token((string)($row['color'] ?? '')),
    ];
}

function create_temp_customer(PDO $conn, array &$cleanup): array
{
    $suffix = date('YmdHis') . '-' . bin2hex(random_bytes(3));
    $email = 'smoke-customer-' . $suffix . '@example.com';
    $passwordHash = password_hash('SmokePass#' . bin2hex(random_bytes(2)), PASSWORD_DEFAULT);
    $activateCode = bin2hex(random_bytes(8));
    $today = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO users (email, password, type, firstname, lastname, address, phone, gender, dob, photo, status, activate_code, created_on, referral)
        VALUES (:email, :password, :type, :firstname, :lastname, :address, :phone, :gender, :dob, :photo, :status, :activate_code, :created_on, :referral)");
    $stmt->execute([
        'email' => $email,
        'password' => $passwordHash,
        'type' => 0,
        'firstname' => 'Smoke',
        'lastname' => 'Customer',
        'address' => 'Integration Test Address',
        'phone' => '08000000000',
        'gender' => 'Other',
        'dob' => '',
        'photo' => '',
        'status' => 1,
        'activate_code' => $activateCode,
        'created_on' => $today,
        'referral' => 'integration-test',
    ]);

    $userId = (int)$conn->lastInsertId();
    $cleanup['temp_user_ids'][] = $userId;

    return [
        'id' => $userId,
        'email' => $email,
        'phone' => '08000000000',
        'address' => 'Integration Test Address',
    ];
}

function fetch_one(PDO $conn, string $sql, array $params = []): ?array
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}

function cleanup_sale(PDO $conn, int $saleId): void
{
    if ($saleId <= 0) {
        return;
    }

    $deletePayments = $conn->prepare("DELETE FROM offline_payments WHERE sales_id = :sales_id");
    $deletePayments->execute(['sales_id' => $saleId]);

    $deleteDetails = $conn->prepare("DELETE FROM details WHERE sales_id = :sales_id");
    $deleteDetails->execute(['sales_id' => $saleId]);

    $deleteSale = $conn->prepare("DELETE FROM sales WHERE id = :id");
    $deleteSale->execute(['id' => $saleId]);
}

function run_cleanup(array &$cleanup): void
{
    global $pdo;

    $conn = $pdo->open();
    try {
        if (!empty($cleanup['bank_sale']['sale_id'])) {
            $saleId = (int)$cleanup['bank_sale']['sale_id'];
            if (!empty($cleanup['bank_sale']['confirmed']) && !empty($cleanup['bank_sale']['product_id']) && !empty($cleanup['bank_sale']['quantity'])) {
                $restore = $conn->prepare("UPDATE products SET qty = qty + :quantity WHERE id = :id");
                $restore->execute([
                    'quantity' => (int)$cleanup['bank_sale']['quantity'],
                    'id' => (int)$cleanup['bank_sale']['product_id'],
                ]);
            }
            cleanup_sale($conn, $saleId);
        }

        foreach ($cleanup['offline_sale_ids'] as $saleId) {
            cleanup_sale($conn, (int)$saleId);
        }

        foreach ($cleanup['category_ids'] as $categoryId) {
            $row = fetch_one($conn, "SELECT cat_image FROM category WHERE id = :id LIMIT 1", ['id' => (int)$categoryId]);
            $delete = $conn->prepare("DELETE FROM category WHERE id = :id");
            $delete->execute(['id' => (int)$categoryId]);
            $image = trim((string)($row['cat_image'] ?? ''));
            if ($image !== '') {
                $imagePath = $GLOBALS['root'] . '/images/' . ltrim($image, '/');
                if (is_file($imagePath)) {
                    @unlink($imagePath);
                }
            }
        }

        foreach ($cleanup['shipping_ids'] as $shippingId) {
            $delete = $conn->prepare("DELETE FROM shippings WHERE id = :id");
            $delete->execute(['id' => (int)$shippingId]);
        }

        foreach ($cleanup['coupon_ids'] as $couponId) {
            $delete = $conn->prepare("DELETE FROM coupons WHERE id = :id");
            $delete->execute(['id' => (int)$couponId]);
        }

        foreach ($cleanup['temp_user_ids'] as $userId) {
            $deleteCart = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $deleteCart->execute(['user_id' => (int)$userId]);

            $deleteUser = $conn->prepare("DELETE FROM users WHERE id = :id");
            $deleteUser->execute(['id' => (int)$userId]);
        }
    } finally {
        $pdo->close();
    }

    foreach ($cleanup['temp_files'] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }

    foreach ($cleanup['session_ids'] as $sessionId) {
        $sessionPath = rtrim((string)session_save_path(), DIRECTORY_SEPARATOR);
        if ($sessionPath === '') {
            continue;
        }

        $sessionFile = $sessionPath . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
        if (is_file($sessionFile)) {
            @unlink($sessionFile);
        }
    }
}

$adminCookieFile = make_cookie_file('bolakaz-admin-destructive-');
$customerCookieFile = make_cookie_file('bolakaz-customer-destructive-');

register_shutdown_function(static function () use (&$cleanup, $adminCookieFile, $customerCookieFile): void {
    if (is_file($adminCookieFile)) {
        @unlink($adminCookieFile);
    }
    if (is_file($customerCookieFile)) {
        @unlink($customerCookieFile);
    }
});

$failures = 0;

try {
    $conn = $pdo->open();
    $adminId = find_active_admin_id($conn);
    $product = find_checkout_product($conn);
    $tempCustomer = create_temp_customer($conn, $cleanup);
    $pdo->close();

    if ($adminId <= 0) {
        throw new RuntimeException('No active admin user was found');
    }

    $adminSession = seed_session('admin', $adminId, $cleanup);
    $customerSession = seed_session('user', (int)$tempCustomer['id'], $cleanup);

    record_result(true, 'Admin session seed', 'Temporary admin session created for integration flows', $failures);
    record_result(true, 'Customer session seed', 'Temporary customer account and session created', $failures);

    $categoryName = 'Smoke Category ' . date('YmdHis') . '-' . bin2hex(random_bytes(2));
    $updatedCategoryName = $categoryName . ' Updated';
    $categoryImage = create_temp_png($cleanup);

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/category_add.php',
        [
            'name' => $categoryName,
            'status' => 'active',
            'is_parent' => '1',
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers'],
        ['cat-image' => $categoryImage]
    );
    assert_redirect($response, 'category');

    $conn = $pdo->open();
    $category = fetch_one($conn, "SELECT id, name, status FROM category WHERE name = :name ORDER BY id DESC LIMIT 1", ['name' => $categoryName]);
    $pdo->close();
    if (!$category) {
        $categoryPage = request_web(
            $baseUrl,
            $adminCookieFile,
            'GET',
            'admin/category',
            [],
            [],
            $adminSession['default_headers']
        );
        $alert = extract_admin_alert_text((string)$categoryPage['body']);
        $detail = 'Category add did not create a record';
        if ($alert !== '') {
            $detail .= ': ' . $alert;
        } else {
            $detail .= ' (category page status ' . (int)$categoryPage['status'];
            $location = trim((string)($categoryPage['headers']['location'] ?? ''));
            if ($location !== '') {
                $detail .= ', redirected to ' . $location;
            }
            $detail .= ')';
        }
        throw new RuntimeException($detail);
    }
    $categoryId = (int)$category['id'];
    $cleanup['category_ids'][] = $categoryId;

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/category_edit.php',
        [
            'id' => $categoryId,
            'name' => $updatedCategoryName,
            'status' => 'inactive',
            'is_parent' => '1',
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'category');

    $conn = $pdo->open();
    $category = fetch_one($conn, "SELECT id, name, status FROM category WHERE id = :id LIMIT 1", ['id' => $categoryId]);
    $pdo->close();
    if (!$category || (string)$category['name'] !== $updatedCategoryName || (string)$category['status'] !== 'inactive') {
        throw new RuntimeException('Category edit did not persist the expected values');
    }

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/category_delete.php',
        [
            'id' => $categoryId,
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'category');

    $conn = $pdo->open();
    $category = fetch_one($conn, "SELECT id FROM category WHERE id = :id LIMIT 1", ['id' => $categoryId]);
    $pdo->close();
    if ($category) {
        throw new RuntimeException('Category delete did not remove the record');
    }
    remove_cleanup_id($cleanup['category_ids'], $categoryId);

    record_result(true, 'Category CRUD flow', 'Create, update, and delete completed with cleanup', $failures);

    $shippingType = 'Smoke Shipping ' . date('YmdHis') . '-' . bin2hex(random_bytes(2));
    $shippingTypeUpdated = $shippingType . ' Updated';

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/shipping_add.php',
        [
            'type' => $shippingType,
            'price' => '1234.56',
            'status' => 'active',
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'shipping');

    $conn = $pdo->open();
    $shipping = fetch_one($conn, "SELECT id, type, price, status FROM shippings WHERE type = :type ORDER BY id DESC LIMIT 1", ['type' => $shippingType]);
    $pdo->close();
    if (!$shipping) {
        throw new RuntimeException('Shipping add did not create a record');
    }
    $shippingId = (int)$shipping['id'];
    $cleanup['shipping_ids'][] = $shippingId;

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/shipping_edit.php',
        [
            'id' => $shippingId,
            'type' => $shippingTypeUpdated,
            'price' => '2345.67',
            'status' => 'inactive',
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'shipping');

    $conn = $pdo->open();
    $shipping = fetch_one($conn, "SELECT id, type, price, status FROM shippings WHERE id = :id LIMIT 1", ['id' => $shippingId]);
    $pdo->close();
    if (!$shipping || (string)$shipping['type'] !== $shippingTypeUpdated || (string)$shipping['status'] !== 'inactive') {
        throw new RuntimeException('Shipping edit did not persist the expected values');
    }

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/shipping_delete.php',
        [
            'id' => $shippingId,
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'shipping');

    $conn = $pdo->open();
    $shipping = fetch_one($conn, "SELECT id FROM shippings WHERE id = :id LIMIT 1", ['id' => $shippingId]);
    $pdo->close();
    if ($shipping) {
        throw new RuntimeException('Shipping delete did not remove the record');
    }
    remove_cleanup_id($cleanup['shipping_ids'], $shippingId);

    record_result(true, 'Shipping CRUD flow', 'Create, update, and delete completed with cleanup', $failures);

    $couponCode = 'SMOKE-' . strtoupper(bin2hex(random_bytes(3)));
    $couponCodeUpdated = $couponCode . '-UPD';
    $expireDate = date('Y-m-d', strtotime('+30 days'));

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/coupon_add.php',
        [
            'code' => $couponCode,
            'type' => 'fixed',
            'value' => '500',
            'status' => 'active',
            'expire_date' => $expireDate,
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'coupon');

    $conn = $pdo->open();
    $coupon = fetch_one($conn, "SELECT id, code, type, value, status FROM coupons WHERE code = :code ORDER BY id DESC LIMIT 1", ['code' => $couponCode]);
    $pdo->close();
    if (!$coupon) {
        throw new RuntimeException('Coupon add did not create a record');
    }
    $couponId = (int)$coupon['id'];
    $cleanup['coupon_ids'][] = $couponId;

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/coupon_edit.php',
        [
            'id' => $couponId,
            'code' => $couponCodeUpdated,
            'type' => 'percent',
            'value' => '15',
            'status' => 'inactive',
            'expire_date' => $expireDate,
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'coupon');

    $conn = $pdo->open();
    $coupon = fetch_one($conn, "SELECT id, code, type, value, status FROM coupons WHERE id = :id LIMIT 1", ['id' => $couponId]);
    $pdo->close();
    if (!$coupon || (string)$coupon['code'] !== $couponCodeUpdated || (string)$coupon['type'] !== 'percent' || (string)$coupon['status'] !== 'inactive') {
        throw new RuntimeException('Coupon edit did not persist the expected values');
    }

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/coupon_delete.php',
        [
            'id' => $couponId,
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'coupon');

    $conn = $pdo->open();
    $coupon = fetch_one($conn, "SELECT id FROM coupons WHERE id = :id LIMIT 1", ['id' => $couponId]);
    $pdo->close();
    if ($coupon) {
        throw new RuntimeException('Coupon delete did not remove the record');
    }
    remove_cleanup_id($cleanup['coupon_ids'], $couponId);

    record_result(true, 'Coupon CRUD flow', 'Create, update, and delete completed with cleanup', $failures);

    $offlineCustomerName = 'Smoke Offline ' . date('YmdHis') . '-' . bin2hex(random_bytes(2));
    $today = date('Y-m-d');
    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/offline_sales_add.php',
        [
            'add' => '1',
            'user_id' => '0',
            'customer_name' => $offlineCustomerName,
            'customer_phone' => '08000000001',
            'sales_date' => $today,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'products' => [$product['id']],
            'qty' => [1],
            'initial_payment' => '0',
            'payment_method' => 'Cash',
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'offline_sales');

    $conn = $pdo->open();
    $offlineSale = fetch_one($conn, "SELECT id, payment_status FROM sales WHERE is_offline = 1 AND customer_name = :customer_name ORDER BY id DESC LIMIT 1", ['customer_name' => $offlineCustomerName]);
    if (!$offlineSale) {
        $pdo->close();
        throw new RuntimeException('Offline sale creation did not produce a sales record');
    }
    $offlineSaleId = (int)$offlineSale['id'];
    $cleanup['offline_sale_ids'][] = $offlineSaleId;

    $totalRow = fetch_one($conn, "SELECT COALESCE(SUM(details.quantity * products.price), 0) AS total_amount FROM details LEFT JOIN products ON products.id = details.product_id WHERE details.sales_id = :sales_id", ['sales_id' => $offlineSaleId]);
    $offlineTotal = (float)($totalRow['total_amount'] ?? 0);
    $pdo->close();
    if ($offlineTotal <= 0) {
        throw new RuntimeException('Offline sale total was not calculated');
    }

    $response = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/offline_payment_add.php',
        [
            'add_payment' => '1',
            'sales_id' => $offlineSaleId,
            'amount' => (string)$offlineTotal,
            'payment_method' => 'Cash',
            'payment_date' => $today,
            '_csrf' => $adminSession['csrf'],
        ],
        ['X-CSRF-Token: ' . $adminSession['csrf']],
        $adminSession['default_headers']
    );
    assert_redirect($response, 'offline_sales');

    $conn = $pdo->open();
    $offlineSale = fetch_one($conn, "SELECT payment_status FROM sales WHERE id = :id LIMIT 1", ['id' => $offlineSaleId]);
    $pdo->close();
    if (!$offlineSale || strtolower((string)$offlineSale['payment_status']) !== 'paid') {
        throw new RuntimeException('Offline sale did not reach paid status after payment');
    }

    record_result(true, 'Offline sale payment flow', 'Offline sale was created, paid, and marked as paid', $failures);

    $cartResponse = request_web(
        $baseUrl,
        $customerCookieFile,
        'POST',
        'cart_add.php',
        [
            'id' => $product['id'],
            'quantity' => 1,
            'size' => $product['size'],
            'color' => $product['color'],
        ],
        [
            'Accept: application/json',
            'X-CSRF-Token: ' . $customerSession['csrf'],
        ],
        $customerSession['default_headers']
    );
    assert_no_runtime_errors($cartResponse, $errorMarkers);
    $cartJson = response_json($cartResponse);
    if (!empty($cartJson['error'])) {
        throw new RuntimeException('Cart add failed: ' . (string)($cartJson['message'] ?? 'Unknown error'));
    }

    $bankTransferResponse = request_web(
        $baseUrl,
        $customerCookieFile,
        'POST',
        'bank_transfer.php',
        [
            'phone' => $tempCustomer['phone'],
            'email-address' => $tempCustomer['email'],
            'address1' => $tempCustomer['address'],
            'address2' => 'Integration Test Address 2',
        ],
        [
            'Accept: application/json',
            'X-CSRF-Token: ' . $customerSession['csrf'],
        ],
        $customerSession['default_headers']
    );
    assert_no_runtime_errors($bankTransferResponse, $errorMarkers);
    $bankTransferJson = response_json($bankTransferResponse);
    if (empty($bankTransferJson['success'])) {
        throw new RuntimeException('Bank transfer checkout failed: ' . (string)($bankTransferJson['message'] ?? 'Unknown error'));
    }

    $conn = $pdo->open();
    $bankSale = fetch_one($conn, "SELECT id, Status, tx_ref FROM sales WHERE user_id = :user_id AND tx_ref LIKE 'BKBTRF-%' ORDER BY id DESC LIMIT 1", ['user_id' => (int)$tempCustomer['id']]);
    $pdo->close();
    if (!$bankSale) {
        throw new RuntimeException('Bank transfer checkout did not create a pending sale');
    }

    $cleanup['bank_sale'] = [
        'sale_id' => (int)$bankSale['id'],
        'product_id' => (int)$product['id'],
        'quantity' => 1,
        'confirmed' => false,
    ];

    $confirmResponse = request_web(
        $baseUrl,
        $adminCookieFile,
        'POST',
        'admin/confirm_bank_transfer.php',
        [
            'id' => (int)$bankSale['id'],
        ],
        [
            'Accept: application/json',
            'X-CSRF-Token: ' . $adminSession['csrf'],
        ],
        $adminSession['default_headers']
    );
    assert_no_runtime_errors($confirmResponse, $errorMarkers);
    $confirmJson = response_json($confirmResponse);
    if (empty($confirmJson['success'])) {
        throw new RuntimeException('Admin bank transfer confirmation failed: ' . (string)($confirmJson['message'] ?? 'Unknown error'));
    }

    $cleanup['bank_sale']['confirmed'] = true;

    $conn = $pdo->open();
    $bankSale = fetch_one($conn, "SELECT Status FROM sales WHERE id = :id LIMIT 1", ['id' => (int)$cleanup['bank_sale']['sale_id']]);
    $updatedProduct = fetch_one($conn, "SELECT qty FROM products WHERE id = :id LIMIT 1", ['id' => (int)$product['id']]);
    $pdo->close();
    if (!$bankSale || strtolower((string)$bankSale['Status']) !== 'success') {
        throw new RuntimeException('Confirmed bank transfer order did not reach success status');
    }
    if (!$updatedProduct || (int)$updatedProduct['qty'] !== ((int)$product['qty'] - 1)) {
        throw new RuntimeException('Confirmed bank transfer did not deduct product stock as expected');
    }

    record_result(true, 'Customer bank transfer flow', 'Customer order was created, admin confirmed it, and stock changed as expected', $failures);
} catch (Throwable $e) {
    record_result(false, 'Destructive integration flow', $e->getMessage(), $failures);
} finally {
    run_cleanup($cleanup);
}

if ($failures > 0) {
    echo PHP_EOL . 'Destructive integration checks failed: ' . $failures . PHP_EOL;
    exit(1);
}

echo PHP_EOL . 'Destructive integration checks passed.' . PHP_EOL;
