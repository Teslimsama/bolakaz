<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$code = trim((string)($_POST['code'] ?? ''));
	$type = trim((string)($_POST['type'] ?? ''));
	$value = (float)($_POST['value'] ?? 0);
	$status = trim((string)($_POST['status'] ?? ''));
	$expire_date = trim((string)($_POST['expire_date'] ?? ''));
	$influencer_id = isset($_POST['influencer_id']) && $_POST['influencer_id'] !== '' ? (int)$_POST['influencer_id'] : null;

	if ($code === '' || !in_array($type, ['fixed', 'percent'], true) || $value < 0 || !in_array($status, ['active', 'inactive'], true)) {
		$_SESSION['error'] = 'Please provide valid coupon details';
		header('location: coupon.php');
		exit;
	}

	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM coupons WHERE code=:code");
	$stmt->execute(['code' => $code]);
	$row = $stmt->fetch();

	if ($row['numrows'] > 0) {
		$_SESSION['error'] = 'Coupon code already exists';
	} else {
		try {
			$conn->beginTransaction();
			$stmt = $conn->prepare("INSERT INTO coupons (code, type, value, status, expire_date, influencer_id, created_at, updated_at) 
									VALUES (:code, :type, :value, :status, :expire_date, :influencer_id, NOW(), NOW())");
			$stmt->execute([
				'code' => $code,
				'type' => $type,
				'value' => $value,
				'status' => $status,
				'expire_date' => ($expire_date !== '' ? $expire_date : null),
				'influencer_id' => $influencer_id
			]);
			$couponId = (int) $conn->lastInsertId();
			sync_enqueue_or_fail($conn, 'coupon', $couponId);
			$conn->commit();
			$_SESSION['success'] = 'Coupon added successfully';
		} catch (Throwable $e) {
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error'] = $e->getMessage();
		}
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Invalid request method';
}

header('location: coupon.php');
