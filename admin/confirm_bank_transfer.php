<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

$response = ['success' => false, 'message' => 'Unable to confirm bank transfer.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($response);
    exit;
}

$saleId = (int)($_POST['id'] ?? 0);
if ($saleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale ID.']);
    exit;
}

$conn = $pdo->open();

try {
    $conn->beginTransaction();

    $saleStmt = $conn->prepare("SELECT id, tx_ref, txid, Status FROM sales WHERE id = :id LIMIT 1 FOR UPDATE");
    $saleStmt->execute(['id' => $saleId]);
    $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sale) {
        throw new RuntimeException('Sale not found.');
    }

    $txRef = (string)($sale['tx_ref'] ?? '');
    $currentStatus = strtolower(trim((string)($sale['Status'] ?? '')));
    $isBankTransfer = (strpos($txRef, 'BKBTRF-') === 0);
    if (!$isBankTransfer) {
        throw new RuntimeException('This order is not a bank transfer transaction.');
    }

    if ($currentStatus === 'success' || $currentStatus === 'successful') {
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Bank transfer was already confirmed.']);
        exit;
    }

    $detailsStmt = $conn->prepare("SELECT details.product_id, details.quantity, products.qty, products.name
        FROM details
        INNER JOIN products ON products.id = details.product_id
        WHERE details.sales_id = :sales_id
        FOR UPDATE");
    $detailsStmt->execute(['sales_id' => $saleId]);
    $items = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new RuntimeException('Order has no items to fulfill.');
    }

    foreach ($items as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $qtyNeeded = max(1, (int)($item['quantity'] ?? 0));
        $stock = (int)($item['qty'] ?? 0);
        $name = (string)($item['name'] ?? 'item');

        if ($productId <= 0) {
            throw new RuntimeException('Order contains an invalid product.');
        }

        if ($stock < $qtyNeeded) {
            throw new RuntimeException('Insufficient stock for ' . $name . '.');
        }

        $deductStmt = $conn->prepare("UPDATE products SET qty = qty - :quantity WHERE id = :id AND qty >= :quantity");
        $deductStmt->execute([
            'quantity' => $qtyNeeded,
            'id' => $productId,
        ]);

        if ($deductStmt->rowCount() !== 1) {
            throw new RuntimeException('Failed to reserve stock. Please retry.');
        }
    }

    $updateSale = $conn->prepare("UPDATE sales SET Status = :status, txid = :txid WHERE id = :id");
    $updateSale->execute([
        'status' => 'success',
        'txid' => time(),
        'id' => $saleId,
    ]);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Bank transfer confirmed and stock updated.']);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $pdo->close();
}
