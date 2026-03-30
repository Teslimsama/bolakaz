<?php
include 'session.php';
require_once __DIR__ . '/../lib/customer_accounts.php';

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
	$stmt = $conn->prepare("SELECT id, email, firstname, lastname, address, phone, type, status, account_state, is_placeholder_email FROM users WHERE id=:id LIMIT 1");
	$stmt->execute(['id' => $id]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$row) {
		echo json_encode(['error' => true, 'message' => 'User not found']);
		exit;
	}

	$row['full_name'] = app_customer_full_name($row);
	if (!app_customer_has_real_email($row)) {
		$row['email'] = '';
	}
	$row['account_state'] = app_customer_row_state($conn, $row);
	$row['error'] = false;
	echo json_encode($row);
} catch (Throwable $e) {
	echo json_encode(['error' => true, 'message' => 'Unable to fetch user']);
} finally {
	$pdo->close();
}
