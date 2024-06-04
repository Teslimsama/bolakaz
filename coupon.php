<?php
include 'session.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['coupon_code'])) {
    $couponCode = $_POST['coupon_code'];
    $userId = $user['id'];

    // Function to apply coupon
    function applyCoupon($couponCode, $userId)
    {
        global $conn;
        // print_r($conn);
        
        // Retrieve the coupon by code
        $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = :code");
        $stmt->execute(['code' => $couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if the coupon exists and is active
        if (!$coupon || $coupon['status'] !== 'active') {
            $_SESSION['error'] = 'Invalid or inactive coupon code, Please try again';
            header('Location: cart.php'); // Redirect to the cart page
            exit();
        }

        // Check if the coupon is expired
        if ($coupon['expire_date'] && strtotime($coupon['expire_date']) < time()) {
            $_SESSION['error'] = 'The coupon has expired, Please try another one';
            header('Location: cart.php'); // Redirect to the cart page
            exit();
        }

        // Calculate the total price of the cart items for the authenticated user
        $stmt = $conn->prepare("SELECT * FROM cart LEFT JOIN products on products.id=cart.product_id WHERE user_id=:user_id");
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_price = number_format($result['price'], 2);
        // print_r($result);

        // Calculate the discount value based on coupon type
        $discount = 0;
        if ($coupon['type'] == 'fixed') {
            $discount = $coupon['value'];
        } elseif ($coupon['type'] == 'percent') {
            $discount = ($total_price * $coupon['value']) / 100;
        }

        // Store the coupon details in the session
        $_SESSION['coupon'] = [
            'id' => $coupon['id'],
            'influencer_id' => $coupon['influencer_id'],
            'code' => $coupon['code'],
            'value' => $discount
        ];

        // Set a success message and redirect back
        $_SESSION['success'] = 'Coupon successfully applied';
        header('Location: cart.php'); // Redirect to the cart page
        exit();
    }

    // Apply the coupon
    applyCoupon($couponCode, $userId);
}
