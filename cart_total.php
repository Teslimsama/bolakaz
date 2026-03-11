<?php
	include 'session.php';
	require_once __DIR__ . '/lib/catalog_v2.php';

	if(isset($_SESSION['user'])){
		$conn = $pdo->open();

		$stmt = $conn->prepare("SELECT cart.quantity, cart.variant_id, products.price FROM cart LEFT JOIN products on products.id=cart.product_id WHERE user_id=:user_id");
		$stmt->execute(['user_id'=>$user['id']]);

		$total = 0;
		foreach($stmt as $row){
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
		}

		$pdo->close();

		echo json_encode($total);
	}
