<?php
include 'session.php';

if (isset($_POST['add'])) {
	$type = $_POST['type'];
	$price = $_POST['price'];

	$conn = $pdo->open();

	// Check if the shipping type already exists
	$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM shippings WHERE type = :type");
	$stmt->execute(['type' => $type]);
	$row = $stmt->fetch();

	if ($row['numrows'] > 0) {
		$_SESSION['error'] = 'Shipping method already exists';
	} else {
		try {
			// Insert new shipping method into the shippings table
			$stmt = $conn->prepare("INSERT INTO shippings (type, price) VALUES (:type, :price)");
			$stmt->execute(['type' => $type, 'price' => $price]);
			$_SESSION['success'] = 'Shipping method added successfully';
		} catch (PDOException $e) {
			$_SESSION['error'] = $e->getMessage();
		}
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up shipping form first';
}
header('location: shipping.php');
