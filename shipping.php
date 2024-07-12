<?php
include 'session.php';

if (isset($_POST['shipping_id'])) {
    $shipping_id = $_POST['shipping_id'];

    // Fetch the shipping option from the database (optional)
    $conn = $pdo->open();
    $stmt = $conn->prepare("SELECT * FROM shippings WHERE id = :id");
    $stmt->execute(['id' => $shipping_id]);
    $shipping = $stmt->fetch();
    $pdo->close();

    if ($shipping) {
        // Update session

        $_SESSION['shipping'] = [
            'shipping_id' => $shipping['id'],
            'shipping_name' => $shipping['type'],
            'shipping_price' => $shipping['price']
        ];
        echo 'Session updated';
    } else {
        echo 'Invalid shipping option';
    }
} else {
    echo 'No shipping option selected';
}
