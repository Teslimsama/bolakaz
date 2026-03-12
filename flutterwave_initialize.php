<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

include 'session.php';
require_once __DIR__ . '/lib/payment_checkout.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (empty($user['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in to continue.']);
    exit();
}

$secret = trim((string)($_ENV['FLUTTERWAVE_SECRET_KEY'] ?? getenv('FLUTTERWAVE_SECRET_KEY') ?? ''));
if ($secret === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment configuration is missing.']);
    exit();
}

$phone = trim((string)($_POST['phone'] ?? ''));
$address1 = trim((string)($_POST['address1'] ?? ''));
$address2 = trim((string)($_POST['address2'] ?? ''));
$email = trim((string)($user['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'A valid account email is required.']);
    exit();
}

$conn = $pdo->open();
try {
    $snapshot = app_checkout_snapshot($conn, (int)$user['id']);
    $amountMinor = (int)round($snapshot['total'] * 100);
    $amountMajor = round($snapshot['total'], 2);
    $txRef = app_payment_build_ref('BKFLW');

    $payload = [
        'tx_ref' => $txRef,
        'amount' => $amountMajor,
        'currency' => 'NGN',
        'redirect_url' => app_payment_base_url() . '/sales.php',
        'payment_options' => 'card,mobilemoney,ussd',
        'customer' => [
            'email' => $email,
            'phonenumber' => $phone,
            'name' => trim((string)($user['firstname'] ?? '') . ' ' . (string)($user['lastname'] ?? '')),
        ],
        'meta' => [
            'user_id' => (int)$user['id'],
            'phone' => $phone,
            'address1' => $address1,
            'address2' => $address2,
        ],
        'customizations' => [
            'title' => 'Bolakaz Checkout',
            'description' => 'Payment for cart items',
        ],
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.flutterwave.com/v3/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secret,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new RuntimeException('Could not reach Flutterwave.');
    }

    $result = json_decode((string)$response, true);
    if (!is_array($result) || ($result['status'] ?? '') !== 'success' || empty($result['data']['link'])) {
        throw new RuntimeException((string)($result['message'] ?? 'Failed to initialize Flutterwave payment.'));
    }

    app_store_payment_intent([
        'provider' => 'flutterwave',
        'tx_ref' => $txRef,
        'amount_minor' => $amountMinor,
        'currency' => 'NGN',
        'user_id' => (int)$user['id'],
        'email' => $email,
        'phone' => $phone,
        'address1' => $address1,
        'address2' => $address2,
        'shipping_id' => (int)$snapshot['shipping_id'],
        'coupon_id' => (int)$snapshot['coupon_id'],
        'created_at' => time(),
    ]);

    echo json_encode([
        'success' => true,
        'link' => (string)$result['data']['link'],
        'reference' => $txRef,
    ]);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $pdo->close();
}
