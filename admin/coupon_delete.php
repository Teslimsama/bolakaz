<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) {
		$_SESSION['error'] = 'Invalid coupon selected';
		header('location: coupon.php');
		exit;
	}

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("SELECT * FROM coupons WHERE id=:id");
		$stmt->execute(['id' => $id]);
		$coupon = $stmt->fetch();

		if ($coupon) {
			$conn->beginTransaction();
			sync_enqueue_delete_or_fail($conn, 'coupon', $coupon);
			$stmt = $conn->prepare("DELETE FROM coupons WHERE id=:id");
			$stmt->execute(['id' => $id]);
			$conn->commit();

			$_SESSION['success'] = 'Coupon deleted successfully';
		} else {
			$_SESSION['error'] = 'Coupon not found';
		}
	} catch (Throwable $e) {
		if ($conn->inTransaction()) {
			$conn->rollBack();
		}
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}

header('location: coupon.php');
