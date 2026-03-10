<?php
	include 'session.php';

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['error' => true, 'message' => 'Invalid request method']);
		exit();
	}

	$conn = $pdo->open();

	$output = array('error'=>false);

	$id = (int)($_POST['id'] ?? 0);
	$quantity = (int)($_POST['quantity'] ?? 1);
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

	$productStmt = $conn->prepare("SELECT size, color FROM products WHERE id = :id LIMIT 1");
	$productStmt->execute(['id' => $id]);
	$product = $productStmt->fetch();
	if (!$product) {
		$output['error'] = true;
		$output['message'] = 'Product not found.';
		$pdo->close();
		echo json_encode($output);
		exit();
	}

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

	if(isset($_SESSION['user'])){
		$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM cart WHERE user_id=:user_id AND product_id=:product_id AND size=:size AND color=:color");
		$stmt->execute([
			'user_id'=>$user['id'],
			'product_id'=>$id,
			'size' => $size,
			'color' => $color
		]);
		$row = $stmt->fetch();
		if($row['numrows'] < 1){
			try{
				$stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, size, color) VALUES (:user_id, :product_id, :quantity, :size, :color)");
				$stmt->execute(['user_id'=>$user['id'], 'product_id'=>$id, 'quantity'=>$quantity, 'size'=>$size, 'color'=>$color]);
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
			if ($rowProductId === $id && $rowSize === $size && $rowColor === $color) {
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
