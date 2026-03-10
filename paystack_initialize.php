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

$secret = trim((string)($_ENV['PAYSTACK_SECRET_KEY'] ?? getenv('PAYSTACK_SECRET_KEY') ?? ''));
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
    $amountKobo = (int)round($snapshot['total'] * 100);
    $txRef = app_payment_build_ref('BKPAY');

    $callbackUrl = app_payment_base_url() . '/transact_verify.php';
    $payload = [
        'amount' => $amountKobo,
        'email' => $email,
        'reference' => $txRef,
        'callback_url' => $callbackUrl,
        'metadata' => [
            'custom_fields' => [[
                'user_id' => (int)$user['id'],
                'phone' => $phone,
                'address1' => $address1,
                'address2' => $address2,
            ]],
        ],
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.paystack.co/transaction/initialize',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'authorization: Bearer ' . $secret,
            'content-type: application/json',
            'cache-control: no-cache',
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new RuntimeException('Could not reach Paystack.');
    }

    $result = json_decode((string)$response, true);
    if (!is_array($result) || empty($result['status']) || empty($result['data']['authorization_url']) || empty($result['data']['reference'])) {
        throw new RuntimeException('Failed to initialize Paystack payment.');
    }

    app_store_payment_intent([
        'provider' => 'paystack',
        'tx_ref' => (string)$result['data']['reference'],
        'amount_minor' => $amountKobo,
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
        'authorization_url' => (string)$result['data']['authorization_url'],
        'reference' => (string)$result['data']['reference'],
    ]);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $pdo->close();
}
