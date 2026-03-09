<?php
include 'session.php';
$conn = $pdo->open();

$output = '';

if (isset($_SESSION['user']) && isset($user['id'])) {
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $row) {
            $stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM cart WHERE user_id=:user_id AND product_id=:product_id");
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
        $stmt = $conn->prepare("SELECT *, cart.id AS cartid FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user");
        $stmt->execute(['user' => $user['id']]);
        foreach ($stmt as $row) {
            $image = app_image_url($row['photo'] ?? '');
            $subtotal = (float)$row['price'] * (int)$row['quantity'];
            $output .= "
                     <tr>
                            <td class='align-middle'><img src='" . e($image) . "' alt='" . e($row['name']) . "' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">" . e($row['name']) . "</td>
                            <td class='align-middle'>" . app_money($row['price']) . "</td>
                            <td class='align-middle'>
                                <div class='input-group quantity mx-auto' style='width: 100px;'>
                                    <div class='input-group-btn'>
                                        <button id='minus' class='btn btn-sm btn-primary btn-minus minus' data-id='" . (int)$row['cartid'] . "' >
                                        <i class='fa fa-minus'></i>
                                        </button>
                                    </div>
                                    <input type='text' class='form-control form-control-sm bg-secondary text-center'  value='" . (int)$row['quantity'] . "' id='qty_" . (int)$row['cartid'] . "'>
                                    <div class='input-group-btn'>
                                        <button id='add' class='btn btn-sm btn-primary btn-plus add' data-id='" . (int)$row['cartid'] . "'>
                                            <i class='fa fa-plus'></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class='align-middle w-80'>" . app_money($subtotal) . "</td>
                            <td class='align-middle'><button type='submit' name='remove' data-id='" . (int)$row['cartid'] . "' class='btn btn-sm btn-primary cart_delete'><i class='fa fa-times'></i></button></td>
                        </tr>
                        ";
        }
    } catch (PDOException $e) {
        $output .= e($e->getMessage());
    }
} else {
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $row) {
            $stmt = $conn->prepare("SELECT *, products.name AS prodname FROM products WHERE products.id=:id");
            $stmt->execute(['id' => $row['productid']]);
            $product = $stmt->fetch();
            if (!$product) {
                continue;
            }
            $image = app_image_url($product['photo'] ?? '');
            $subtotal = (float)$product['price'] * (int)$row['quantity'];
            $output .= "
                    <tr>
                        <td class='align-middle'><img src='" . e($image) . "' alt='" . e($product['prodname']) . "' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">" . e($product['prodname']) . "</td>
                        <td class='align-middle'>" . app_money($product['price']) . "</td>
                        <td class='align-middle'>
                            <div class='input-group quantity mx-auto' style='width: 100px;'>
                                <div class='input-group-btn'>
                                    <button id='minus' class='btn btn-sm btn-primary btn-minus minus' data-id='" . (int)$row['productid'] . "' >
                                    <i class='fa fa-minus'></i>
                                    </button>
                                </div>
                                <input type='text' class='form-control form-control-sm bg-secondary text-center' value='" . (int)$row['quantity'] . "' id='qty_" . (int)$row['productid'] . "'>
                                <div class='input-group-btn'>
                                    <button id='add' class='btn btn-sm btn-primary btn-plus add' data-id='" . (int)$row['productid'] . "'>
                                        <i class='fa fa-plus'></i>
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class='align-middle w-80'>" . app_money($subtotal) . "</td>
                        <td class='align-middle'><button type='submit' name='remove' data-id='" . (int)$row['productid'] . "' class='btn btn-sm btn-primary cart_delete'><i class='fa fa-times'></i></button></td>
                    </tr>
                ";
        }
    } else {
        $output .= "
            <tr>
                <td colspan='6' align='center'>Shopping cart empty</td>
            <tr>
        ";
    }
}

$pdo->close();
echo json_encode($output);
