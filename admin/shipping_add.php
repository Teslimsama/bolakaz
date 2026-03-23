<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$type = trim((string)($_POST['type'] ?? ''));
	$price = (float)($_POST['price'] ?? 0);
	$status = trim((string)($_POST['status'] ?? 'active'));

	if ($type === '' || $price < 0 || !in_array($status, ['active', 'inactive'], true)) {
		$_SESSION['error'] = 'Please provide valid shipping details';
		header('location: shipping');
		exit;
	}

	$conn = $pdo->open();

	// Check if the shipping type already exists
	$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM shippings WHERE type = :type");
	$stmt->execute(['type' => $type]);
	$row = $stmt->fetch();

	if ($row['numrows'] > 0) {
		$_SESSION['error'] = 'Shipping method already exists';
	} else {
		try {
			$conn->beginTransaction();

			$stmt = $conn->prepare("INSERT INTO shippings (type, price, status) VALUES (:type, :price, :status)");
			$stmt->execute(['type' => $type, 'price' => $price, 'status' => $status]);
			$shippingId = (int) $conn->lastInsertId();
			sync_enqueue_or_fail($conn, 'shipping', $shippingId);
			$conn->commit();
			$_SESSION['success'] = 'Shipping method added successfully';
		} catch (Throwable $e) {
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error'] = 'Unable to add shipping method';
		}
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}
header('location: shipping');
