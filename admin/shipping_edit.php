<?php
include 'session.php';

if (isset($_POST['edit'])) {
	$id = $_POST['id'];
	$type = $_POST['type'];
	$price = $_POST['price'];
	$status = $_POST['status'];

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("UPDATE shippings SET type = :type, price = :price, status = :status WHERE id = :id");
		$stmt->execute(['type' => $type, 'price' => $price, 'status' => $status, 'id' => $id]);
		$_SESSION['success'] = 'Shipping method updated successfully';
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up edit shipping form first';
}

header('location: shipping.php');
