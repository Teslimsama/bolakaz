<?php
include 'session.php';
require_once __DIR__ . '/../lib/product_payload.php';

if (isset($_POST['id'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT products.*, products.id AS prodid, products.name AS prodname,
		cat.name AS catname, cat.parent_id AS cat_parent_id, cat.is_parent AS cat_is_parent,
		subcat.name AS subcatname, parentcat.name AS parent_catname
		FROM products
		LEFT JOIN category AS cat ON cat.id = products.category_id
		LEFT JOIN category AS subcat ON subcat.id = products.subcategory_id
		LEFT JOIN category AS parentcat ON parentcat.id = cat.parent_id
		WHERE products.id=:id");
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

	$categoryId = (int)($row['category_id'] ?? 0);
	$subcategoryId = (int)($row['subcategory_id'] ?? 0);
	$catParentId = (int)($row['cat_parent_id'] ?? 0);
	$catIsParent = (int)($row['cat_is_parent'] ?? 0);

	// Legacy compatibility: some rows store subcategory in category_id and leave subcategory_id empty.
	if ($subcategoryId <= 0 && $categoryId > 0 && $catParentId > 0 && $catIsParent === 0) {
		$row['edit_category_id'] = $catParentId;
		$row['edit_subcategory_id'] = $categoryId;
		$row['edit_category_name'] = (string)($row['parent_catname'] ?? '');
		$row['edit_subcategory_name'] = (string)($row['catname'] ?? '');
	} else {
		$row['edit_category_id'] = $categoryId;
		$row['edit_subcategory_id'] = ($subcategoryId > 0 ? $subcategoryId : null);
		$row['edit_category_name'] = (string)($row['catname'] ?? '');
		$row['edit_subcategory_name'] = (string)($row['subcatname'] ?? '');
	}

	echo json_encode($row);
}
