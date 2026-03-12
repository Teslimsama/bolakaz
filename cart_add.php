<?php
	include 'session.php';
	require_once __DIR__ . '/lib/catalog_v2.php';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['error' => true, 'message' => 'Invalid request method']);
		exit();
	}

	$conn = $pdo->open();

	$output = array('error'=>false);

	$id = (int)($_POST['id'] ?? 0);
	$quantity = (int)($_POST['quantity'] ?? 1);
	$variantId = (int)($_POST['variant_id'] ?? 0);
	$catalogMode = strtolower(trim((string)($_POST['catalog_mode'] ?? 'legacy')));
	$size = trim((string)($_POST['size'] ?? $_POST['size '] ?? ''));
	$color = trim((string)($_POST['color'] ?? ''));

	if ($id <= 0) {
		$output['error'] = true;
		$output['message'] = 'Invalid product.';
		$pdo->close();
		echo json_encode($output);
		exit();
	}

	if ($quantity < 1) {
		$quantity = 1;
	}

	$productStmt = $conn->prepare("SELECT id, size, color, product_status FROM products WHERE id = :id LIMIT 1");
	$productStmt->execute(['id' => $id]);
	$product = $productStmt->fetch();
	if (!$product) {
		$output['error'] = true;
		$output['message'] = 'Product not found.';
		$pdo->close();
		echo json_encode($output);
		exit();
	}
	if ((int)($product['product_status'] ?? 1) !== 1) {
		$output['error'] = true;
		$output['message'] = 'This product is not available right now.';
		$pdo->close();
		echo json_encode($output);
		exit();
	}

	$useV2 = ($catalogMode === 'v2' && catalog_v2_ready($conn));
	$resolvedVariantId = 0;
	if ($useV2) {
		$mapStmt = $conn->prepare("SELECT product_v2_id FROM product_legacy_map WHERE legacy_product_id = :legacy_id LIMIT 1");
		$mapStmt->execute(['legacy_id' => $id]);
		$productV2Id = (int)$mapStmt->fetchColumn();

		if ($productV2Id <= 0) {
			$output['error'] = true;
			$output['message'] = 'Product variant data is unavailable.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}

		if ($variantId <= 0) {
			$output['error'] = true;
			$output['message'] = 'Please choose a variant.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}

		$variantStmt = $conn->prepare("SELECT id, stock_qty, status FROM product_variants WHERE id = :id AND product_id = :product_id LIMIT 1");
		$variantStmt->execute([
			'id' => $variantId,
			'product_id' => $productV2Id,
		]);
		$variant = $variantStmt->fetch(PDO::FETCH_ASSOC);
		if (!$variant || (string)($variant['status'] ?? '') !== 'active') {
			$output['error'] = true;
			$output['message'] = 'Selected variant is invalid.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}
		if ((int)($variant['stock_qty'] ?? 0) < $quantity) {
			$output['error'] = true;
			$output['message'] = 'Selected quantity is not available for this variant.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}
		$resolvedVariantId = (int)$variant['id'];
	}

	if (!$useV2) {
		$validSizes = array_values(array_filter(array_map('trim', explode(',', (string)($product['size'] ?? ''))), function ($v) {
			return $v !== '';
		}));
		$validColors = array_values(array_filter(array_map('trim', explode(',', (string)($product['color'] ?? ''))), function ($v) {
			return $v !== '';
		}));

		if (!empty($validSizes) && $size === '') {
			$output['error'] = true;
			$output['message'] = 'Please choose a size.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}
		if (!empty($validSizes) && $size !== '' && !in_array($size, $validSizes, true)) {
			$output['error'] = true;
			$output['message'] = 'Selected size is invalid.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}
		if (!empty($validColors) && $color === '') {
			$output['error'] = true;
			$output['message'] = 'Please choose a color.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}
		if (!empty($validColors) && $color !== '' && !in_array($color, $validColors, true)) {
			$output['error'] = true;
			$output['message'] = 'Selected color is invalid.';
			$pdo->close();
			echo json_encode($output);
			exit();
		}
	}

	if(isset($_SESSION['user'])){
		$sql = "SELECT COUNT(*) AS numrows FROM cart WHERE user_id=:user_id AND product_id=:product_id";
		$params = [
			'user_id' => $user['id'],
			'product_id' => $id,
		];
		if ($resolvedVariantId > 0) {
			$sql .= " AND variant_id = :variant_id";
			$params['variant_id'] = $resolvedVariantId;
		} else {
			$sql .= " AND size=:size AND color=:color";
			$params['size'] = $size;
			$params['color'] = $color;
		}
		$stmt = $conn->prepare($sql);
		$stmt->execute($params);
		$row = $stmt->fetch();
		if($row['numrows'] < 1){
			try{
				$stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, variant_id, quantity, size, color) VALUES (:user_id, :product_id, :variant_id, :quantity, :size, :color)");
				$stmt->execute([
					'user_id' => $user['id'],
					'product_id' => $id,
					'variant_id' => ($resolvedVariantId > 0 ? $resolvedVariantId : null),
					'quantity' => $quantity,
					'size' => $size,
					'color' => $color
				]);
				$output['message'] = 'Item added to cart';
				
			}
			catch(PDOException $e){
				$output['error'] = true;
				$output['message'] = $e->getMessage();
			}
		}
		else{
			$output['error'] = true;
			$output['message'] = 'Product already in cart';
		}
	}
	else{
		if(!isset($_SESSION['cart'])){
			$_SESSION['cart'] = array();
		}

		$exists = false;
		foreach($_SESSION['cart'] as $row){
			$rowProductId = (int)($row['productid'] ?? 0);
			$rowSize = trim((string)($row['size'] ?? ''));
			$rowColor = trim((string)($row['color'] ?? ''));
			$rowVariantId = (int)($row['variant_id'] ?? 0);
			if ($rowProductId === $id && $rowSize === $size && $rowColor === $color && $rowVariantId === $resolvedVariantId) {
				$exists = true;
				break;
			}
		}

		if($exists){
			$output['error'] = true;
			$output['message'] = 'Product variation already in cart';
		}
		else{
			$data['productid'] = $id;
			$data['variant_id'] = $resolvedVariantId;
			$data['quantity'] = $quantity;
			$data['size'] = $size;
			$data['color'] = $color;

			if(array_push($_SESSION['cart'], $data)){
				$output['message'] = 'Item added to cart';
			}
			else{
				$output['error'] = true;
				$output['message'] = 'Cannot add item to cart';
			}
		}

	}

	$pdo->close();
	echo json_encode($output);
