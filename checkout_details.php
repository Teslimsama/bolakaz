<?php
include 'session.php';
require_once __DIR__ . '/lib/catalog_v2.php';

$conn = $pdo->open();
$output = '';

if (isset($_SESSION['user']) && isset($user['id'])) {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $row) {
            $productId = (int)($row['productid'] ?? 0);
            $variantId = (int)($row['variant_id'] ?? 0);
            $qty = max(1, (int)($row['quantity'] ?? 1));
            $size = trim((string)($row['size'] ?? ''));
            $color = trim((string)($row['color'] ?? ''));
            if ($productId <= 0) {
                continue;
            }

            $sql = "SELECT COUNT(*) AS numrows FROM cart WHERE user_id=:user_id AND product_id=:product_id";
            $params = ['user_id' => $user['id'], 'product_id' => $productId];
            if ($variantId > 0) {
                $sql .= " AND variant_id = :variant_id";
                $params['variant_id'] = $variantId;
            } else {
                $sql .= " AND size = :size AND color = :color";
                $params['size'] = $size;
                $params['color'] = $color;
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $crow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ((int)($crow['numrows'] ?? 0) < 1) {
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity, size, color)
                    VALUES (:user_id, :product_id, :variant_id, :quantity, :size, :color)");
                $stmt->execute([
                    'user_id' => $user['id'],
                    'product_id' => $productId,
                    'variant_id' => ($variantId > 0 ? $variantId : null),
                    'quantity' => $qty,
                    'size' => $size,
                    'color' => $color,
                ]);
            } else {
                $updateSql = "UPDATE cart SET quantity=:quantity WHERE user_id=:user_id AND product_id=:product_id";
                $updateParams = ['quantity' => $qty, 'user_id' => $user['id'], 'product_id' => $productId];
                if ($variantId > 0) {
                    $updateSql .= " AND variant_id = :variant_id";
                    $updateParams['variant_id'] = $variantId;
                } else {
                    $updateSql .= " AND size = :size AND color = :color";
                    $updateParams['size'] = $size;
                    $updateParams['color'] = $color;
                }
                $stmt = $conn->prepare($updateSql);
                $stmt->execute($updateParams);
            }
        }
        unset($_SESSION['cart']);
    }

    try {
        $total = 0.0;
        $stmt = $conn->prepare("SELECT *, cart.id AS cartid FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user_id");
        $stmt->execute(['user_id' => $user['id']]);
        foreach ($stmt as $row) {
            $linePrice = (float)($row['price'] ?? 0);
            if (catalog_v2_ready($conn) && (int)($row['variant_id'] ?? 0) > 0) {
                $vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
                $vpStmt->execute(['id' => (int)$row['variant_id']]);
                $variantPrice = $vpStmt->fetchColumn();
                if ($variantPrice !== false) {
                    $linePrice = (float)$variantPrice;
                }
            }

            $subtotal = $linePrice * (int)$row['quantity'];
            $total += $subtotal;
            $output .= "
                <div class='d-flex justify-content-between'>
                    <p>" . (int)$row['quantity'] . " &times;</p>
                    <p>" . e($row['name']) . "</p>
                    <p>" . app_money($linePrice) . "</p>
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
            $stmt->execute(['id' => (int)($row['productid'] ?? 0)]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                continue;
            }

            $linePrice = (float)($product['price'] ?? 0);
            if (catalog_v2_ready($conn) && (int)($row['variant_id'] ?? 0) > 0) {
                $vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
                $vpStmt->execute(['id' => (int)$row['variant_id']]);
                $variantPrice = $vpStmt->fetchColumn();
                if ($variantPrice !== false) {
                    $linePrice = (float)$variantPrice;
                }
            }

            $qty = max(1, (int)($row['quantity'] ?? 1));
            $subtotal = $linePrice * $qty;
            $total += $subtotal;
            $output .= "
                <div class='d-flex justify-content-between'>
                    <p>" . $qty . " &times; " . e($product['prodname']) . "</p>
                    <p>" . app_money($linePrice) . "</p>
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

