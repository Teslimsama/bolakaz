<?php
include 'session.php';

$output = '';

$conn = $pdo->open();

$stmt = $conn->prepare("SELECT * FROM shippings WHERE status = 'active'");
$stmt->execute();

foreach ($stmt as $row) {
	$output .= "<input type='hidden' name='shipping_type' value='" . $row['type'] . "'>
        <option value='" . $row['id'] . "' class='append_items'>" . $row['type'] . " - ₦" . number_format($row['price'], 2) . "</option>
    ";
}

$pdo->close();
echo json_encode($output);
