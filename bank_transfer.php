<?php
include 'session.php';
require_once __DIR__ . '/lib/payment_checkout.php';

header('Content-Type: application/json; charset=UTF-8');

$response = ['success' => false, 'message' => 'Unable to process bank transfer.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($response);
    exit;
}

if (empty($user['id'])) {
    http_response_code(401);
    $response['message'] = 'Please sign in to continue.';
    echo json_encode($response);
    exit;
}

$phone = trim((string)filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
$email = trim((string)filter_input(INPUT_POST, 'email-address', FILTER_VALIDATE_EMAIL));
$address1 = trim((string)filter_input(INPUT_POST, 'address1', FILTER_SANITIZE_SPECIAL_CHARS));
$address2 = trim((string)filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_SPECIAL_CHARS));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email address.';
    echo json_encode($response);
    exit;
}

$conn = $pdo->open();
try {
    $snapshot = app_checkout_snapshot($conn, (int)$user['id']);
    $txRef = app_payment_build_ref('BKBTRF');

    $conn->beginTransaction();

    $existingStmt = $conn->prepare("SELECT id FROM sales WHERE tx_ref = :tx_ref LIMIT 1");
    $existingStmt->execute(['tx_ref' => $txRef]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($existing['id'])) {
        throw new RuntimeException('Duplicate bank transfer reference generated. Please retry.');
    }

    $date = date('Y-m-d');
    $insertSale = $conn->prepare("INSERT INTO sales (user_id, tx_ref, txid, Status, shipping_id, coupon_id, phone, email, address_1, address_2, sales_date)
        VALUES (:user_id, :tx_ref, :txid, :status, :shipping_id, :coupon_id, :phone, :email, :address_1, :address_2, :sales_date)");
    $insertSale->execute([
        'user_id' => (int)$user['id'],
        'tx_ref' => $txRef,
        'txid' => null,
        'status' => 'pending',
        'shipping_id' => (int)$snapshot['shipping_id'],
        'coupon_id' => (int)$snapshot['coupon_id'],
        'phone' => $phone,
        'email' => $email,
        'address_1' => $address1,
        'address_2' => $address2,
        'sales_date' => $date,
    ]);
    $salesId = (int)$conn->lastInsertId();

    foreach ($snapshot['items'] as $item) {
        $detailStmt = $conn->prepare("INSERT INTO details (sales_id, product_id, quantity) VALUES (:sales_id, :product_id, :quantity)");
        $detailStmt->execute([
            'sales_id' => $salesId,
            'product_id' => (int)$item['product_id'],
            'quantity' => (int)$item['quantity'],
        ]);
    }

    // Bank transfer remains pending. Do not deduct stock here.
    $clearCart = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
    $clearCart->execute(['user_id' => (int)$user['id']]);

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Bank transfer request submitted. Order is pending confirmation.';
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $response['message'] = $e->getMessage();
} finally {
    $pdo->close();
    echo json_encode($response);
}
