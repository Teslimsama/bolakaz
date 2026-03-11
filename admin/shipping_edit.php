<?php
include 'session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	$type = trim((string)($_POST['type'] ?? ''));
	$price = (float)($_POST['price'] ?? 0);
	$status = trim((string)($_POST['status'] ?? ''));

	if ($id <= 0 || $type === '' || $price < 0 || !in_array($status, ['active', 'inactive'], true)) {
		$_SESSION['error'] = 'Please provide valid shipping details';
		header('location: shipping.php');
		exit;
	}

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("UPDATE shippings SET type = :type, price = :price, status = :status WHERE id = :id");
		$stmt->execute(['type' => $type, 'price' => $price, 'status' => $status, 'id' => $id]);
		$_SESSION['success'] = 'Shipping method updated successfully';
	} catch (PDOException $e) {
		$_SESSION['error'] = 'Unable to update shipping method';
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}

header('location: shipping.php');
