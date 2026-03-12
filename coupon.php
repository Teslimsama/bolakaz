<?php
include 'session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['coupon_code'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: cart.php');
    exit;
}

$couponCode = trim((string)$_POST['coupon_code']);
if ($couponCode === '') {
    $_SESSION['error'] = 'Please enter a coupon code';
    header('Location: cart.php');
    exit;
}

$conn = $pdo->open();
try {
    $couponStmt = $conn->prepare("SELECT * FROM coupons WHERE code = :code LIMIT 1");
    $couponStmt->execute(['code' => $couponCode]);
    $coupon = $couponStmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon || strtolower((string)$coupon['status']) !== 'active') {
        $_SESSION['error'] = 'Invalid or inactive coupon code. Please try again.';
        header('Location: cart.php');
        exit;
    }

    if (!empty($coupon['expire_date']) && strtotime((string)$coupon['expire_date']) < strtotime(date('Y-m-d'))) {
        $_SESSION['error'] = 'This coupon has expired. Please try another one.';
        header('Location: cart.php');
        exit;
    }

    $subtotal = 0.0;
    $userId = isset($user['id']) ? (int)$user['id'] : 0;
    if ($userId > 0) {
        $cartStmt = $conn->prepare("SELECT products.price, cart.quantity
            FROM cart
            LEFT JOIN products ON products.id = cart.product_id
            WHERE cart.user_id = :user_id");
        $cartStmt->execute(['user_id' => $userId]);
        foreach ($cartStmt as $row) {
            $subtotal += (float)($row['price'] ?? 0) * max(1, (int)($row['quantity'] ?? 0));
        }
    } else {
        $sessionCart = $_SESSION['cart'] ?? [];
        if (is_array($sessionCart)) {
            foreach ($sessionCart as $row) {
                $productId = (int)($row['productid'] ?? 0);
                $quantity = max(1, (int)($row['quantity'] ?? 0));
                if ($productId <= 0) {
                    continue;
                }
                $priceStmt = $conn->prepare("SELECT price FROM products WHERE id = :id LIMIT 1");
                $priceStmt->execute(['id' => $productId]);
                $product = $priceStmt->fetch(PDO::FETCH_ASSOC);
                if (!$product) {
                    continue;
                }
                $subtotal += (float)$product['price'] * $quantity;
            }
        }
    }

    if ($subtotal <= 0) {
        $_SESSION['error'] = 'Your cart is empty.';
        header('Location: cart.php');
        exit;
    }

    $discount = 0.0;
    $couponType = strtolower((string)$coupon['type']);
    $couponValue = (float)($coupon['value'] ?? 0);
    if ($couponType === 'fixed') {
        $discount = $couponValue;
    } elseif ($couponType === 'percent') {
        $discount = ($subtotal * $couponValue) / 100;
    }
    if ($discount < 0) {
        $discount = 0;
    }
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }

    $_SESSION['coupon'] = [
        'id' => (int)$coupon['id'],
        'influencer_id' => isset($coupon['influencer_id']) ? (int)$coupon['influencer_id'] : null,
        'code' => (string)$coupon['code'],
        'value' => round($discount, 2),
    ];

    $_SESSION['success'] = 'Coupon successfully applied';
    header('Location: cart.php');
    exit;
} catch (Throwable $e) {
    $_SESSION['error'] = 'Unable to apply coupon right now.';
    header('Location: cart.php');
    exit;
} finally {
    $pdo->close();
}
