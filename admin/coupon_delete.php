<?php
include 'session.php';

if (isset($_POST['delete'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	try {
		// Fetch the coupon details
		$stmt = $conn->prepare("SELECT id FROM coupons WHERE id=:id");
		$stmt->execute(['id' => $id]);
		$coupon = $stmt->fetch();

		if ($coupon) {
			// Delete the coupon record from the database
			$stmt = $conn->prepare("DELETE FROM coupons WHERE id=:id");
			$stmt->execute(['id' => $id]);

			$_SESSION['success'] = 'Coupon deleted successfully';
		} else {
			$_SESSION['error'] = 'Coupon not found';
		}
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Select coupon to delete first';
}

header('location: coupon.php');
