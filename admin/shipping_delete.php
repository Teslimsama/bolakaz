<?php
include 'session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) {
		$_SESSION['error'] = 'Invalid shipping method selected';
		header('location: shipping');
		exit;
	}

	$conn = $pdo->open();

	try {
		// Fetch the shipping record to ensure it exists
		$stmt = $conn->prepare("SELECT * FROM shippings WHERE id = :id");
		$stmt->execute(['id' => $id]);
		$shipping = $stmt->fetch();

		if ($shipping) {
			// Delete the shipping record from the database
			$stmt = $conn->prepare("DELETE FROM shippings WHERE id = :id");
			$stmt->execute(['id' => $id]);

			$_SESSION['success'] = 'Shipping method deleted successfully';
		} else {
			$_SESSION['error'] = 'Shipping method not found';
		}
	} catch (PDOException $e) {
		$_SESSION['error'] = 'Unable to delete shipping method';
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}

header('location: shipping');
