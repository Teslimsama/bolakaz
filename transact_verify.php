<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

include_once 'session.php';
require_once __DIR__ . '/lib/payment_checkout.php';

if (empty($user['id'])) {
    $_SESSION['error'] = 'Please sign in before verifying payment.';
    header('location: checkout#payment');
    exit;
}

$ref = trim((string)filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_SPECIAL_CHARS));
if ($ref === '') {
    $_SESSION['error'] = 'Missing payment reference.';
    header('location: checkout#payment');
    exit;
}

$secret = trim((string)($_ENV['PAYSTACK_SECRET_KEY'] ?? getenv('PAYSTACK_SECRET_KEY') ?? ''));
if ($secret === '') {
    $_SESSION['error'] = 'Payment configuration is missing.';
    header('location: checkout#payment');
    exit;
}

$intent = app_get_payment_intent();
if (!is_array($intent) || ($intent['provider'] ?? '') !== 'paystack') {
    $_SESSION['error'] = 'Payment session expired. Please retry checkout.';
    header('location: checkout#payment');
    exit;
}

if ((string)($intent['tx_ref'] ?? '') !== $ref || (int)($intent['user_id'] ?? 0) !== (int)$user['id']) {
    $_SESSION['error'] = 'Payment reference mismatch.';
    header('location: checkout#payment');
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://api.paystack.co/transaction/verify/' . rawurlencode($ref),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secret,
        'Cache-Control: no-cache',
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    $_SESSION['error'] = 'Payment verification failed. Please try again.';
    header('location: checkout#payment');
    exit;
}

$result = json_decode((string)$response, true);
if (!is_array($result) || empty($result['status']) || !isset($result['data'])) {
    $_SESSION['error'] = 'Invalid verification response.';
    header('location: checkout#payment');
    exit;
}

$data = $result['data'];
$paidStatus = (string)($data['status'] ?? '');
$paidCurrency = strtoupper((string)($data['currency'] ?? ''));
$paidAmount = (int)($data['amount'] ?? 0);
$paidRef = (string)($data['reference'] ?? '');
$gatewayTxId = (int)($data['id'] ?? 0);

if ($paidStatus !== 'success' || $paidRef !== $ref || $paidCurrency !== 'NGN') {
    $_SESSION['error'] = 'Transaction failed verification.';
    header('location: checkout#payment');
    exit;
}

$expectedAmount = (int)($intent['amount_minor'] ?? 0);
if ($expectedAmount <= 0 || $paidAmount !== $expectedAmount) {
    $_SESSION['error'] = 'Paid amount did not match expected checkout total.';
    header('location: checkout#payment');
    exit;
}

$conn = $pdo->open();
try {
    $conn->beginTransaction();
    app_finalize_paid_order(
        $conn,
        (int)$user['id'],
        $ref,
        'paystack',
        'success',
        (string)($intent['email'] ?? (string)$user['email']),
        (string)($intent['phone'] ?? ''),
        (string)($intent['address1'] ?? ''),
        (string)($intent['address2'] ?? ''),
        (int)($intent['shipping_id'] ?? 0),
        (int)($intent['coupon_id'] ?? 0),
        ($gatewayTxId > 0 ? $gatewayTxId : null)
    );
    $conn->commit();

    app_clear_payment_intent();
    $_SESSION['success'] = 'Transaction successful. Thank you.';
    header('location: profile#trans');
    exit;
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    header('location: checkout#payment');
    exit;
} finally {
    $pdo->close();
}
