<?php
include 'session.php';
require_once __DIR__ . '/../lib/product_payload.php';

if (isset($_POST['id'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT *, products.id AS prodid, products.name AS prodname, category.name AS catname, products.size, products.color, products.material, products.brand, products.description, products.additional_info, products.photo, products.price, products.qty, products.product_status FROM products LEFT JOIN category ON category.id=products.category_id WHERE products.id=:id");
	$stmt->execute(['id' => $id]);
	$row = $stmt->fetch();

	$pdo->close();

	// Preparing the selected sizes, colors, and materials as arrays (assuming they are stored as comma-separated values)
	$selectedSizes = product_csv_to_array($row['size'] ?? '');
	$selectedColors = product_csv_to_array($row['color'] ?? '');
	$selectedMaterials = product_csv_to_array($row['material'] ?? '', 80);
	$specs = product_decode_specs($row['additional_info'] ?? '');

	// Prepare data for JSON response with additional product details
	$row['size'] = $selectedSizes;
	$row['color'] = $selectedColors;
	$row['material'] = $selectedMaterials;
	$row['additional_info'] = $specs;
	$row['product_status'] = (int)($row['product_status'] ?? 1);

	echo json_encode($row);
}
