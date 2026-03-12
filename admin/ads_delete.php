<?php
include 'session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) {
		$_SESSION['error'] = 'Invalid ad selected';
		header('location: ads.php');
		exit;
	}

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("DELETE FROM ads WHERE id=:id");
		$stmt->execute(['id' => $id]);

		$_SESSION['success'] = 'Ad deleted successfully';
	} catch (PDOException $e) {
		$_SESSION['error'] = 'Unable to delete ad.';
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}
header('location: ads.php');
