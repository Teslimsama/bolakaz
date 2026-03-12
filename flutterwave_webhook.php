<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

require_once __DIR__ . '/CreateDb.php';
require_once __DIR__ . '/lib/payment_checkout.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$configuredHash = trim((string)($_ENV['FLUTTERWAVE_WEBHOOK_HASH'] ?? getenv('FLUTTERWAVE_WEBHOOK_HASH') ?? ''));
$headerHash = trim((string)($_SERVER['HTTP_VERIF_HASH'] ?? ''));
if ($configuredHash === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Webhook hash not configured.']);
    exit;
}

if ($headerHash === '' || !hash_equals($configuredHash, $headerHash)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid signature.']);
    exit;
}

$rawBody = (string)file_get_contents('php://input');
$event = json_decode($rawBody, true);
if (!is_array($event)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
    exit;
}

$eventType = strtolower(trim((string)($event['event'] ?? '')));
if (!in_array($eventType, ['charge.completed', 'payment.completed'], true)) {
    echo json_encode(['success' => true, 'processed' => false, 'message' => 'Event ignored.']);
    exit;
}

$txRef = trim((string)($event['data']['tx_ref'] ?? ''));
$paidStatus = trim((string)($event['data']['status'] ?? ''));
$gatewayTxId = (int)($event['data']['id'] ?? 0);

if ($txRef === '') {
    echo json_encode(['success' => true, 'processed' => false, 'message' => 'Missing transaction reference.']);
    exit;
}

$conn = $pdo->open();
try {
    $conn->beginTransaction();
    $processed = app_reconcile_sale_from_webhook($conn, $txRef, $paidStatus, ($gatewayTxId > 0 ? $gatewayTxId : null));
    $conn->commit();

    echo json_encode(['success' => true, 'processed' => $processed]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Webhook processing failed.']);
} finally {
    $pdo->close();
}
