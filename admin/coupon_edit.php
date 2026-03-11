<?php
include 'session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	$code = trim((string)($_POST['code'] ?? ''));
	$type = trim((string)($_POST['type'] ?? ''));
	$value = (float)($_POST['value'] ?? 0);
	$status = trim((string)($_POST['status'] ?? ''));
	$expire_date = trim((string)($_POST['expire_date'] ?? ''));
	$influencer_id = isset($_POST['influencer_id']) && $_POST['influencer_id'] !== '' ? (int)$_POST['influencer_id'] : null;

	if ($id <= 0 || $code === '' || !in_array($type, ['fixed', 'percent'], true) || $value < 0 || !in_array($status, ['active', 'inactive'], true)) {
		$_SESSION['error'] = 'Please provide valid coupon details';
		header('location: coupon.php');
		exit;
	}

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("UPDATE coupons SET code=:code, type=:type, value=:value, status=:status, expire_date=:expire_date, influencer_id=:influencer_id WHERE id=:id");
		$stmt->execute([
			'code' => $code,
			'type' => $type,
			'value' => $value,
			'status' => $status,
			'expire_date' => ($expire_date !== '' ? $expire_date : null),
			'influencer_id' => $influencer_id,
			'id' => $id
		]);
		$_SESSION['success'] = 'Coupon updated successfully';
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}

header('location: coupon.php');
