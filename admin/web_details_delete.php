<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

app_admin_require_roles(['admin', 'staff']);

if (isset($_POST['delete'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("SELECT * FROM web_details WHERE id=:id LIMIT 1");
		$stmt->execute(['id' => $id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$row) {
			$_SESSION['error'] = 'Web Detail not found';
			$pdo->close();
			header('location: web_details.php');
			exit;
		}

		$conn->beginTransaction();
		sync_enqueue_delete_or_fail($conn, 'web_details', $row);
		$stmt = $conn->prepare("DELETE FROM web_details WHERE id=:id");
		$stmt->execute(['id' => $id]);
		$conn->commit();

		$_SESSION['success'] = 'Web Detail deleted successfully';
	} catch (Throwable $e) {
		if ($conn->inTransaction()) {
			$conn->rollBack();
		}
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Select Web Detail to delete first';
}
header('location: web_details.php');
