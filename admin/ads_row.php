<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['id'])) {
	echo json_encode(['error' => true, 'message' => 'Invalid request']);
	exit;
}

$id = (int)$_POST['id'];
if ($id <= 0) {
	echo json_encode(['error' => true, 'message' => 'Invalid ad ID']);
	exit;
}

$conn = $pdo->open();
try {
	$stmt = $conn->prepare("SELECT * FROM ads WHERE id=:id LIMIT 1");
	$stmt->execute(['id' => $id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) {
		echo json_encode(['error' => true, 'message' => 'Ad not found']);
		exit;
	}

	$row['error'] = false;
	echo json_encode($row);
} catch (Throwable $e) {
	echo json_encode(['error' => true, 'message' => 'Unable to load ad']);
} finally {
	$pdo->close();
}
