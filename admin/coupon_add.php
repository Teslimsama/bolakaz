<?php
include 'session.php';

if (isset($_POST['add'])) {
	$code = $_POST['code'];
	$type = $_POST['type'];
	$value = $_POST['value'];
	$status = $_POST['status'];
	$expire_date = $_POST['expire_date'];
	$influencer_id = isset($_POST['influencer_id']) ? $_POST['influencer_id'] : null;

	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM coupons WHERE code=:code");
	$stmt->execute(['code' => $code]);
	$row = $stmt->fetch();

	if ($row['numrows'] > 0) {
		$_SESSION['error'] = 'Coupon code already exists';
	} else {
		try {
			$stmt = $conn->prepare("INSERT INTO coupons (code, type, value, status, expire_date, influencer_id, created_at, updated_at) 
									VALUES (:code, :type, :value, :status, :expire_date, :influencer_id, NOW(), NOW())");
			$stmt->execute([
				'code' => $code,
				'type' => $type,
				'value' => $value,
				'status' => $status,
				'expire_date' => $expire_date,
				'influencer_id' => $influencer_id
			]);
			$_SESSION['success'] = 'Coupon added successfully';
		} catch (PDOException $e) {
			$_SESSION['error'] = $e->getMessage();
		}
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up the coupon form first';
}

header('location: coupon.php');
