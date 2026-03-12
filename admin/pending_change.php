<?php
include 'session.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_POST['id']) || !isset($_POST['status'])) {
	echo json_encode(['success' => false, 'message' => 'Invalid request']);
	exit;
}

$id = (int)$_POST['id'];
$status = strtolower(trim((string)$_POST['status']));
$allowed = ['success', 'pending', 'failed'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
	echo json_encode(['success' => false, 'message' => 'Invalid payload']);
	exit;
}

$conn = $pdo->open();

try {
	$saleStmt = $conn->prepare("SELECT tx_ref FROM sales WHERE id = :id LIMIT 1");
	$saleStmt->execute(['id' => $id]);
	$sale = $saleStmt->fetch(PDO::FETCH_ASSOC);

	if (!$sale) {
		echo json_encode(['success' => false, 'message' => 'Sale not found']);
		exit;
	}

	$txRef = (string)($sale['tx_ref'] ?? '');
	if (strpos($txRef, 'BKBTRF-') === 0) {
		echo json_encode(['success' => false, 'message' => 'Use Confirm Payment for bank transfers.']);
		exit;
	}

	$stmt = $conn->prepare("UPDATE sales SET Status = :status WHERE id = :id");
	$stmt->execute(['status' => $status, 'id' => $id]);
	echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (PDOException $e) {
	echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
	$pdo->close();
}
