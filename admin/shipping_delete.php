<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) {
		$_SESSION['error'] = 'Invalid shipping method selected';
		header('location: shipping');
		exit;
	}

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("SELECT * FROM shippings WHERE id = :id");
		$stmt->execute(['id' => $id]);
		$shipping = $stmt->fetch();

		if ($shipping) {
			$conn->beginTransaction();
			sync_enqueue_delete_or_fail($conn, 'shipping', $shipping);
			$stmt = $conn->prepare("DELETE FROM shippings WHERE id = :id");
			$stmt->execute(['id' => $id]);
			$conn->commit();

			$_SESSION['success'] = 'Shipping method deleted successfully';
		} else {
			$_SESSION['error'] = 'Shipping method not found';
		}
	} catch (Throwable $e) {
		if ($conn->inTransaction()) {
			$conn->rollBack();
		}
		$_SESSION['error'] = 'Unable to delete shipping method';
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}

header('location: shipping');
