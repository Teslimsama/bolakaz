<?php
include 'session.php';
require_once __DIR__ . '/lib/catalog_v2.php';

$conn = $pdo->open();
$output = '';

if (isset($_SESSION['user']) && isset($user['id'])) {
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $sessionKey => $row) {
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
                $updateParams = [
                    'quantity' => $qty,
                    'user_id' => $user['id'],
                    'product_id' => $productId,
                ];
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
        $stmt = $conn->prepare("SELECT *, cart.id AS cartid
            FROM cart
            LEFT JOIN products ON products.id = cart.product_id
            WHERE user_id = :user
            ORDER BY cart.id DESC");
        $stmt->execute(['user' => $user['id']]);

        foreach ($stmt as $row) {
            $image = app_image_url($row['photo'] ?? '');
            $linePrice = (float)($row['price'] ?? 0);
            $variantLabel = '';

            if (catalog_v2_ready($conn) && (int)($row['variant_id'] ?? 0) > 0) {
                $vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
                $vpStmt->execute(['id' => (int)$row['variant_id']]);
                $variantPrice = $vpStmt->fetchColumn();
                if ($variantPrice !== false) {
                    $linePrice = (float)$variantPrice;
                }

                $voStmt = $conn->prepare("SELECT a.label, av.value
                    FROM variant_option_values vov
                    INNER JOIN attributes a ON a.id = vov.attribute_id
                    INNER JOIN attribute_values av ON av.id = vov.attribute_value_id
                    WHERE vov.variant_id = :variant_id
                    ORDER BY a.label ASC");
                $voStmt->execute(['variant_id' => (int)$row['variant_id']]);
                $optRows = $voStmt->fetchAll(PDO::FETCH_ASSOC);
                $optText = [];
                foreach ($optRows as $optRow) {
                    $optText[] = trim((string)$optRow['label']) . ': ' . trim((string)$optRow['value']);
                }
                if (!empty($optText)) {
                    $variantLabel = '<br><small class="text-muted">' . e(implode(' | ', $optText)) . '</small>';
                }
            } elseif (trim((string)($row['size'] ?? '')) !== '' || trim((string)($row['color'] ?? '')) !== '') {
                $variantLabel = '<br><small class="text-muted">' . e(trim((string)$row['size'] . ' ' . (string)$row['color'])) . '</small>';
            }

            $subtotal = $linePrice * (int)$row['quantity'];
            $output .= "
                <tr>
                    <td class='align-middle'><img src='" . e($image) . "' alt='" . e($row['name']) . "' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">" . e($row['name']) . $variantLabel . "</td>
                    <td class='align-middle'>" . app_money($linePrice) . "</td>
                    <td class='align-middle'>
                        <div class='input-group quantity mx-auto' style='width: 100px;'>
                            <div class='input-group-btn'>
                                <button id='minus' class='btn btn-sm btn-primary btn-minus minus' data-id='" . (int)$row['cartid'] . "'>
                                    <i class='fa fa-minus'></i>
                                </button>
                            </div>
                            <input type='text' class='form-control form-control-sm bg-secondary text-center' value='" . (int)$row['quantity'] . "' id='qty_" . (int)$row['cartid'] . "'>
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
        foreach ($_SESSION['cart'] as $sessionKey => $row) {
            $productId = (int)($row['productid'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $stmt = $conn->prepare("SELECT *, products.name AS prodname FROM products WHERE products.id=:id");
            $stmt->execute(['id' => $productId]);
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

            $image = app_image_url($product['photo'] ?? '');
            $subtotal = $linePrice * max(1, (int)($row['quantity'] ?? 1));
            $rowId = 's' . (string)$sessionKey;
            $output .= "
                <tr>
                    <td class='align-middle'><img src='" . e($image) . "' alt='" . e($product['prodname']) . "' style='width: 50px; height: 50px; object-fit: cover; border-radius: 6px;' onerror=\"this.onerror=null;this.src='" . e(app_placeholder_image()) . "';\">" . e($product['prodname']) . "</td>
                    <td class='align-middle'>" . app_money($linePrice) . "</td>
                    <td class='align-middle'>
                        <div class='input-group quantity mx-auto' style='width: 100px;'>
                            <div class='input-group-btn'>
                                <button id='minus' class='btn btn-sm btn-primary btn-minus minus' data-id='" . e($rowId) . "'>
                                    <i class='fa fa-minus'></i>
                                </button>
                            </div>
                            <input type='text' class='form-control form-control-sm bg-secondary text-center' value='" . (int)$row['quantity'] . "' id='qty_" . e($rowId) . "'>
                            <div class='input-group-btn'>
                                <button id='add' class='btn btn-sm btn-primary btn-plus add' data-id='" . e($rowId) . "'>
                                    <i class='fa fa-plus'></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td class='align-middle w-80'>" . app_money($subtotal) . "</td>
                    <td class='align-middle'><button type='submit' name='remove' data-id='" . e($rowId) . "' class='btn btn-sm btn-primary cart_delete'><i class='fa fa-times'></i></button></td>
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
