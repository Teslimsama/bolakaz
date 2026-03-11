<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

if (isset($_POST['shipping_id'])) {
    $shipping_id = (int)$_POST['shipping_id'];
    if ($shipping_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid shipping option']);
        exit;
    }

    // Fetch the shipping option from the database (optional)
    $conn = $pdo->open();
    $stmt = $conn->prepare("SELECT * FROM shippings WHERE id = :id AND status = :status LIMIT 1");
    $stmt->execute(['id' => $shipping_id, 'status' => 'active']);
    $shipping = $stmt->fetch();
    $pdo->close();

    if ($shipping) {
        // Update session

        $_SESSION['shipping'] = [
            'shipping_id' => $shipping['id'],
            'shipping_name' => $shipping['type'],
            'shipping_price' => $shipping['price']
        ];
        echo json_encode(['success' => true, 'message' => 'Shipping option updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid shipping option']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No shipping option selected']);
}
