<?php
include 'session.php';

if (isset($_POST['delete'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("DELETE FROM web_details WHERE id=:id");
		$stmt->execute(['id' => $id]);

		$_SESSION['success'] = 'Web Detail deleted successfully';
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Select Web Detail to delete first';
}
header('location: web_details.php');
