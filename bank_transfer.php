<?php
include 'session.php';
$conn = $pdo->open();
$response = ['success' => false, 'message' => '']; // Initialize response array
// Generate transaction reference
$txid = '';
$tx_ref = 'BolaKaz' . rand(99999, 10000000) . 'BTRF';
$Tstatus = 'pending';
date_default_timezone_set('Africa/Lagos');
$date = date("Y-m-d");

// Validate and sanitize POST data
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email-address', FILTER_VALIDATE_EMAIL);
$address1 = filter_input(INPUT_POST, 'address1', FILTER_SANITIZE_SPECIAL_CHARS);
$address2 = filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$email) {
	throw new Exception('Invalid email address.');
}

// Get coupon and shipping data from session
$coupon_id = isset($_SESSION['coupon']['id']) ? $_SESSION['coupon']['id'] : 0;
$shipping_id = isset($_SESSION['shipping']['id']) ? $_SESSION['shipping']['id'] : 0;

try {

	// Insert sales data
	$stmt = $conn->prepare("INSERT INTO sales (user_id, tx_ref, txid, Status, shipping_id, coupon_id, phone, email, address_1, address_2, sales_date) VALUES (:user_id, :tx_ref, :txid, :Status, :shipping_id, :coupon_id, :phone, :email, :address1, :address2, :sales_date)");
	$stmt->execute([
		'user_id' => $user['id'],
		'tx_ref' => $tx_ref,
		'txid' => $txid,
		'Status' => $Tstatus,
		'shipping_id' => $shipping_id,
		'coupon_id' => $coupon_id,
		'phone' => $phone,
		'email' => $email,
		'address1' => $address1,
		'address2' => $address2,
		'sales_date' => $date
	]);
	$salesid = $conn->lastInsertId();

	// Process cart items
	$stmt = $conn->prepare("SELECT * FROM cart LEFT JOIN products ON products.id = cart.product_id WHERE user_id = :user_id");
	$stmt->execute(['user_id' => $user['id']]);

	foreach ($stmt as $row) {
		// Insert sale details
		$stmt = $conn->prepare("INSERT INTO details (sales_id, product_id, quantity) VALUES (:sales_id, :product_id, :quantity)");
		$stmt->execute([
			'sales_id' => $salesid,
			'product_id' => $row['product_id'],
			'quantity' => $row['quantity']
		]);

		// Update product quantity
		$new_value = $row['qty'] - $row['quantity'];
		$stmt = $conn->prepare("UPDATE products SET qty = :new_value WHERE id = :id");
		$stmt->execute(['new_value' => $new_value, 'id' => $row['product_id']]);
	}

	// Clear user's cart
	$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
	$stmt->execute(['user_id' => $user['id']]);

	$response['success'] = true;
	$response['message'] = 'Transaction successful. Thank you.';
} catch (PDOException $e) {
	// Log the error
	error_log($e->getMessage());
	$response['message'] = 'An error occurred. Please try again later.';
} catch (Exception $e) {
	$response['message'] = $e->getMessage();
} finally {
	$pdo->close();
	echo json_encode($response);
}
