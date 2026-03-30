<?php
require_once __DIR__ . '/catalog_v2.php';
require_once __DIR__ . '/sales_snapshot.php';

if (!function_exists('app_db_has_column')) {
    function app_db_has_column(PDO $conn, string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $stmt = $conn->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column_name");
        $stmt->execute(['column_name' => $column]);
        $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        return $cache[$key];
    }
}

if (!function_exists('app_payment_base_url')) {
    function app_payment_base_url(): string
    {
        $configured = trim((string)($_ENV['APP_URL'] ?? getenv('APP_URL') ?? ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $basePath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
        return $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');
    }
}

if (!function_exists('app_payment_build_ref')) {
    function app_payment_build_ref(string $prefix): string
    {
        return strtoupper($prefix) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }
}

if (!function_exists('app_checkout_snapshot')) {
    function app_checkout_snapshot(PDO $conn, int $userId): array
    {
        $stmt = $conn->prepare("SELECT cart.product_id, cart.variant_id, cart.quantity, products.price, products.qty
            FROM cart
            LEFT JOIN products ON products.id = cart.product_id
            WHERE cart.user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new RuntimeException('Your cart is empty.');
        }

        $subtotal = 0.0;
        $normalized = [];
        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $variantId = (int)($item['variant_id'] ?? 0);
            $quantity = max(1, (int)($item['quantity'] ?? 0));
            $price = (float)($item['price'] ?? 0);
            $stock = (int)($item['qty'] ?? 0);
            if ($productId <= 0 || $price < 0) {
                continue;
            }

            if (catalog_v2_ready($conn) && $variantId > 0) {
                $variantStmt = $conn->prepare("SELECT price, stock_qty, status FROM product_variants WHERE id = :id LIMIT 1");
                $variantStmt->execute(['id' => $variantId]);
                $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);
                if (!$variant || (string)($variant['status'] ?? '') !== 'active') {
                    throw new RuntimeException('One or more cart variants are unavailable.');
                }
                $price = (float)($variant['price'] ?? $price);
                $stock = (int)($variant['stock_qty'] ?? 0);
            }

            $subtotal += ($price * $quantity);
            $normalized[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $price,
                'stock' => $stock,
            ];
        }

        if (empty($normalized)) {
            throw new RuntimeException('Your cart is empty.');
        }

        $discount = isset($_SESSION['coupon']['value']) ? (float)$_SESSION['coupon']['value'] : 0.0;
        if ($discount < 0) {
            $discount = 0.0;
        }
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        $shipping = isset($_SESSION['shipping']['shipping_price']) ? (float)$_SESSION['shipping']['shipping_price'] : 0.0;
        if ($shipping < 0) {
            $shipping = 0.0;
        }

        $shippingId = isset($_SESSION['shipping']['shipping_id']) ? (int)$_SESSION['shipping']['shipping_id'] : 0;
        $couponId = isset($_SESSION['coupon']['id']) ? (int)$_SESSION['coupon']['id'] : 0;

        $total = round(($subtotal - $discount + $shipping), 2);
        if ($total <= 0) {
            throw new RuntimeException('Invalid checkout total.');
        }

        return [
            'items' => $normalized,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $total,
            'shipping_id' => $shippingId,
            'coupon_id' => $couponId,
        ];
    }
}

if (!function_exists('app_store_payment_intent')) {
    function app_store_payment_intent(array $intent): void
    {
        $_SESSION['payment_intent'] = $intent;
    }
}

if (!function_exists('app_get_payment_intent')) {
    function app_get_payment_intent(): ?array
    {
        return isset($_SESSION['payment_intent']) && is_array($_SESSION['payment_intent'])
            ? $_SESSION['payment_intent']
            : null;
    }
}

if (!function_exists('app_clear_payment_intent')) {
    function app_clear_payment_intent(): void
    {
        unset($_SESSION['payment_intent']);
    }
}

if (!function_exists('app_finalize_paid_order')) {
    function app_finalize_paid_order(
        PDO $conn,
        int $userId,
        string $txRef,
        string $provider,
        string $status,
        string $email,
        string $phone,
        string $address1,
        string $address2,
        int $shippingId,
        int $couponId,
        ?int $gatewayTxId = null
    ): int {
        $existingStmt = $conn->prepare("SELECT id FROM sales WHERE tx_ref = :tx_ref LIMIT 1");
        $existingStmt->execute(['tx_ref' => $txRef]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($existing['id'])) {
            return (int)$existing['id'];
        }

        $date = date('Y-m-d');
        $txid = ($gatewayTxId !== null && $gatewayTxId > 0) ? $gatewayTxId : null;
        $customerName = '';

        $userStmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE id = :id LIMIT 1");
        $userStmt->execute(['id' => $userId]);
        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($userRow) {
            $customerName = trim((string) ($userRow['firstname'] ?? '') . ' ' . (string) ($userRow['lastname'] ?? ''));
        }

        $insertSale = $conn->prepare("INSERT INTO sales (user_id, tx_ref, txid, Status, shipping_id, coupon_id, customer_name, phone, email, address_1, address_2, sales_date)
            VALUES (:user_id, :tx_ref, :txid, :status, :shipping_id, :coupon_id, :customer_name, :phone, :email, :address_1, :address_2, :sales_date)");
        $insertSale->execute([
            'user_id' => $userId,
            'tx_ref' => $txRef,
            'txid' => $txid,
            'status' => $status,
            'shipping_id' => $shippingId,
            'coupon_id' => $couponId,
            'customer_name' => ($customerName !== '' ? $customerName : null),
            'phone' => $phone,
            'email' => $email,
            'address_1' => $address1,
            'address_2' => $address2,
            'sales_date' => $date,
        ]);
        $salesId = (int)$conn->lastInsertId();

        $cartStmt = $conn->prepare("SELECT cart.product_id, cart.variant_id, cart.quantity, products.qty, products.name, products.slug, products.price
            FROM cart
            LEFT JOIN products ON products.id = cart.product_id
            WHERE cart.user_id = :user_id");
        $cartStmt->execute(['user_id' => $userId]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($cartItems)) {
            throw new RuntimeException('Cart is empty during order finalization.');
        }

        foreach ($cartItems as $row) {
            $productId = (int)($row['product_id'] ?? 0);
            $variantId = (int)($row['variant_id'] ?? 0);
            $quantity = max(1, (int)($row['quantity'] ?? 0));
            $stock = (int)($row['qty'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            if (catalog_v2_ready($conn) && $variantId > 0) {
                $variantLock = $conn->prepare("SELECT id, stock_qty, status FROM product_variants WHERE id = :id FOR UPDATE");
                $variantLock->execute(['id' => $variantId]);
                $variant = $variantLock->fetch(PDO::FETCH_ASSOC);
                if (!$variant || (string)($variant['status'] ?? '') !== 'active') {
                    throw new RuntimeException('One or more product variants are unavailable.');
                }
                if ((int)$variant['stock_qty'] < $quantity) {
                    throw new RuntimeException('Insufficient stock for one or more variants.');
                }
            } else {
                if ($stock < $quantity) {
                    throw new RuntimeException('Insufficient stock for one or more items.');
                }
            }

            $unitPrice = (float) ($row['price'] ?? 0);
            if (catalog_v2_ready($conn) && $variantId > 0) {
                $variantPriceStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
                $variantPriceStmt->execute(['id' => $variantId]);
                $variantPrice = $variantPriceStmt->fetchColumn();
                if ($variantPrice !== false && $variantPrice !== null) {
                    $unitPrice = (float) $variantPrice;
                }
            }

            app_sales_insert_detail_row(
                $conn,
                $salesId,
                $productId,
                $quantity,
                $unitPrice,
                (string) ($row['name'] ?? ''),
                (string) ($row['slug'] ?? ''),
                ($variantId > 0 ? $variantId : null)
            );

            if (catalog_v2_ready($conn) && $variantId > 0) {
                $updateVariantStock = $conn->prepare("UPDATE product_variants SET stock_qty = stock_qty - :quantity WHERE id = :id");
                $updateVariantStock->execute(['quantity' => $quantity, 'id' => $variantId]);
            } else {
                $updateStock = $conn->prepare("UPDATE products SET qty = qty - :quantity WHERE id = :id");
                $updateStock->execute(['quantity' => $quantity, 'id' => $productId]);
            }
        }

        $clearCart = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
        $clearCart->execute(['user_id' => $userId]);

        return $salesId;
    }
}

if (!function_exists('app_reconcile_sale_from_webhook')) {
    function app_reconcile_sale_from_webhook(PDO $conn, string $txRef, string $paidStatus, ?int $gatewayTxId = null): bool
    {
        $txRef = trim($txRef);
        if ($txRef === '') {
            return false;
        }

        $normalizedStatus = strtolower(trim($paidStatus));
        $successStates = ['success', 'successful', 'completed', 'paid'];
        if (!in_array($normalizedStatus, $successStates, true)) {
            return false;
        }

        $saleStmt = $conn->prepare("SELECT id, Status FROM sales WHERE tx_ref = :tx_ref LIMIT 1 FOR UPDATE");
        $saleStmt->execute(['tx_ref' => $txRef]);
        $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
        if (!$sale) {
            return false;
        }

        $currentStatus = strtolower(trim((string)($sale['Status'] ?? '')));
        $targetStatus = in_array($currentStatus, ['success', 'successful'], true) ? (string)$sale['Status'] : 'success';

        $updateStmt = $conn->prepare("UPDATE sales
            SET Status = :status,
                txid = COALESCE(:txid, txid)
            WHERE id = :id");
        $updateStmt->execute([
            'status' => $targetStatus,
            'txid' => ($gatewayTxId !== null && $gatewayTxId > 0) ? $gatewayTxId : null,
            'id' => (int)$sale['id'],
        ]);

        return true;
    }
}
