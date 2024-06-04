<?php
include 'session.php';

if (isset($_POST['edit'])) {
	$id = $_POST['id'];
	$code = $_POST['code'];
	$type = $_POST['type'];
	$value = $_POST['value'];
	$status = $_POST['status'];
	$expire_date = $_POST['expire_date'];
	$influencer_id = $_POST['influencer_id'];

	$conn = $pdo->open();

	try {
		$stmt = $conn->prepare("UPDATE coupons SET code=:code, type=:type, value=:value, status=:status, expire_date=:expire_date, influencer_id=:influencer_id WHERE id=:id");
		$stmt->execute([
			'code' => $code,
			'type' => $type,
			'value' => $value,
			'status' => $status,
			'expire_date' => $expire_date,
			'influencer_id' => $influencer_id,
			'id' => $id
		]);
		$_SESSION['success'] = 'Coupon updated successfully';
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up edit coupon form first';
}

header('location: coupon.php');
