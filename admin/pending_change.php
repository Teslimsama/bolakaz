<?php
include 'session.php';

if (isset($_POST['id']) && isset($_POST['status'])) {
	$id = $_POST['id'];
	$status = $_POST['status'];
	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("UPDATE sales SET Status=:Status WHERE id=:id");
		$stmt->execute(['Status' => $status, 'id' => $id]);
		echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
	} catch (PDOException $e) {
		echo json_encode(['success' => false, 'message' => $e->getMessage()]);
	}

	$pdo->close();
} else {
	echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
