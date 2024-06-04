<?php
include 'session.php';

$output = '';

$conn = $pdo->open();

$stmt = $conn->prepare("SELECT * FROM coupons");
$stmt->execute();

foreach ($stmt as $row) {
	$output .= "<input type='hidden' name='coupon_code' value='" . $row['code'] . "'>
			<option value='" . $row['id'] . "' class='append_items'>" . $row['code'] . "</option>
		";
}

$pdo->close();
echo json_encode($output);
