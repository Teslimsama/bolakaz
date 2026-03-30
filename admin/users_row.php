<?php
include 'session.php';

app_admin_require_roles(['admin']);

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['id'])) {
	echo json_encode(['error' => true, 'message' => 'Invalid request']);
	exit;
}

$id = (int)$_POST['id'];
if ($id <= 0) {
	echo json_encode(['error' => true, 'message' => 'Invalid user ID']);
	exit;
}

$conn = $pdo->open();
try {
	$stmt = $conn->prepare("SELECT id, email, firstname, lastname, address, phone FROM users WHERE id=:id LIMIT 1");
	$stmt->execute(['id' => $id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) {
		echo json_encode(['error' => true, 'message' => 'User not found']);
		exit;
	}

	$row['error'] = false;
	echo json_encode($row);
} catch (Throwable $e) {
	echo json_encode(['error' => true, 'message' => 'Unable to fetch user']);
} finally {
	$pdo->close();
}
