<?php
include 'session.php';

if (isset($_POST['delete'])) {
	$id = $_POST['id'];

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
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Select shipping method to delete first';
}

header('location: shipping.php');
