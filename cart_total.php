<?php
	include 'session.php';
	require_once __DIR__ . '/lib/catalog_v2.php';
$conn = $pdo->open();
$total = 0.0;

if (!empty($user['id'])) {
	$stmt = $conn->prepare("SELECT cart.quantity, cart.variant_id, products.price FROM cart LEFT JOIN products on products.id=cart.product_id WHERE user_id=:user_id");
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
		$total += $linePrice * (int)($row['quantity'] ?? 0);
	}
} else {
	$sessionCart = $_SESSION['cart'] ?? [];
	if (is_array($sessionCart)) {
		foreach ($sessionCart as $row) {
			$productId = (int)($row['productid'] ?? 0);
			$variantId = (int)($row['variant_id'] ?? 0);
			$quantity = max(1, (int)($row['quantity'] ?? 1));
			if ($productId <= 0) {
				continue;
			}

			$priceStmt = $conn->prepare("SELECT price FROM products WHERE id = :id LIMIT 1");
			$priceStmt->execute(['id' => $productId]);
			$product = $priceStmt->fetch(PDO::FETCH_ASSOC);
			if (!$product) {
				continue;
			}

			$linePrice = (float)($product['price'] ?? 0);
			if (catalog_v2_ready($conn) && $variantId > 0) {
				$vpStmt = $conn->prepare("SELECT price FROM product_variants WHERE id = :id LIMIT 1");
				$vpStmt->execute(['id' => $variantId]);
				$variantPrice = $vpStmt->fetchColumn();
				if ($variantPrice !== false) {
					$linePrice = (float)$variantPrice;
				}
			}

			$total += $linePrice * $quantity;
		}
	}
}

$pdo->close();

echo json_encode($total);
