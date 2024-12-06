<?php
include 'session.php';

if (isset($_POST['id'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT *, products.id AS prodid, products.name AS prodname, category.name AS catname, products.size, products.color, products.material, products.brand, products.description, products.photo, products.price, products.qty FROM products LEFT JOIN category ON category.id=products.category_id WHERE products.id=:id");
	$stmt->execute(['id' => $id]);
	$row = $stmt->fetch();

	$pdo->close();

	// Preparing the selected sizes, colors, and materials as arrays (assuming they are stored as comma-separated values)
	$selectedSizes = explode(',', $row['size']);
	$selectedColors = explode(',', $row['color']);
	$selectedMaterials = explode(',', $row['material']);

	// Prepare data for JSON response with additional product details
	$row['size'] = $selectedSizes;
	$row['color'] = $selectedColors;
	$row['material'] = $selectedMaterials;

	echo json_encode($row);
}
