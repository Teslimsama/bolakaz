<?php
include 'session.php';
$conn = $pdo->open();

$output = '';

if (isset($_SESSION['user']) && isset($user['id'])) {
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $row) {
            $stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM cart WHERE user_id=:user_id AND product_id=:product_id");
            $stmt->execute(['user_id' => $user['id'], 'product_id' => $row['productid']]);
            $crow = $stmt->fetch();
            if ((int)$crow['numrows'] < 1) {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                $stmt->execute(['user_id' => $user['id'], 'product_id' => $row['productid'], 'quantity' => $row['quantity']]);
            } else {
                $stmt = $conn->prepare("UPDATE cart SET quantity=:quantity WHERE user_id=:user_id AND product_id=:product_id");
                $stmt->execute(['quantity' => $row['quantity'], 'user_id' => $user['id'], 'product_id' => $row['productid']]);
            }
        }
        unset($_SESSION['cart']);
    }

    try {
        $total = 0.0;
        $stmt = $conn->prepare("SELECT *, cart.id AS cartid FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user_id");
        $stmt->execute(['user_id' => $user['id']]);
        foreach ($stmt as $row) {
            $subtotal = (float)$row['price'] * (int)$row['quantity'];
            $total += $subtotal;
            $output .= "
                <div class='d-flex justify-content-between'>
                    <p>" . (int)$row['quantity'] . " &times;</p>
                    <p>" . e($row['name']) . "</p>
                    <p>" . app_money($row['price']) . "</p>
                </div>
            ";
        }

        $discount = isset($_SESSION['coupon']) ? (float)$_SESSION['coupon']['value'] : 0;
        $shipping = isset($_SESSION['shipping']['shipping_price']) ? (float)$_SESSION['shipping']['shipping_price'] : 0;
        $total_c = $total - $discount + $shipping;

        $output .= "
            <hr class='mt-0'>
            <div class='d-flex justify-content-between mb-3 pt-1'>
                <h6 class='font-weight-medium'>Subtotal</h6>
                <h6 class='font-weight-medium'>" . app_money($total) . "</h6>
            </div>
            <div class='d-flex justify-content-between'>
                <h6 class='font-weight-medium'>Shipping</h6>
                <h6 class='font-weight-medium'>" . app_money($shipping) . "</h6>
            </div>
            <div class='d-flex justify-content-between'>
                <h6 class='font-weight-medium'>Discount</h6>
                <h6 class='font-weight-medium'>- " . app_money($discount) . "</h6>
            </div>
            <div class='card-footer border-secondary bg-transparent'>
                <div class='d-flex justify-content-between mt-2'>
                    <h5 class='font-weight-bold'>Total</h5>
                    <input type='hidden' value='" . e($total_c) . "' id='amount'>
                    <h5 class='font-weight-bold'>" . app_money($total_c) . "</h5>
                </div>
            </div>
        ";
    } catch (PDOException $e) {
        $output .= "<p>Error: " . e($e->getMessage()) . "</p>";
    }
} else {
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $total = 0.0;
        foreach ($_SESSION['cart'] as $row) {
            $stmt = $conn->prepare("SELECT *, products.name AS prodname FROM products WHERE products.id=:id");
            $stmt->execute(['id' => $row['productid']]);
            $product = $stmt->fetch();
            if (!$product) {
                continue;
            }

            $subtotal = (float)$product['price'] * (int)$row['quantity'];
            $total += $subtotal;
            $output .= "
                <div class='d-flex justify-content-between'>
                    <p>" . (int)$row['quantity'] . " &times; " . e($product['prodname']) . "</p>
                    <p>" . app_money($product['price']) . "</p>
                </div>
            ";
        }

        $discount = isset($_SESSION['coupon']) ? (float)$_SESSION['coupon']['value'] : 0;
        $shipping = isset($_SESSION['shipping']['shipping_price']) ? (float)$_SESSION['shipping']['shipping_price'] : 0;
        $total_c = $total - $discount + $shipping;

        $output .= "
            <hr class='mt-0'>
            <div class='d-flex justify-content-between mb-3 pt-1'>
                <h6 class='font-weight-medium'>Subtotal</h6>
                <h6 class='font-weight-medium'>" . app_money($total) . "</h6>
            </div>
            <div class='d-flex justify-content-between'>
                <h6 class='font-weight-medium'>Shipping</h6>
                <h6 class='font-weight-medium'>" . app_money($shipping) . "</h6>
            </div>
            <div class='d-flex justify-content-between'>
                <h6 class='font-weight-medium'>Discount</h6>
                <h6 class='font-weight-medium'>- " . app_money($discount) . "</h6>
            </div>
            <div class='card-footer border-secondary bg-transparent'>
                <div class='d-flex justify-content-between mt-2'>
                    <h5 class='font-weight-bold'>Total</h5>
                    <input type='hidden' value='" . e($total_c) . "' id='amount'>
                    <h5 class='font-weight-bold'>" . app_money($total_c) . "</h5>
                </div>
            </div>
        ";
    } else {
        $output .= "<p>Shopping cart empty</p>";
    }
}

$pdo->close();
echo json_encode($output);
