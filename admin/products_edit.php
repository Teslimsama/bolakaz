<?php
include 'session.php';
include 'slugify.php';

if (isset($_POST['edit'])) {
	$id = $_POST['id'];
	$name = $_POST['name'];
	$slug = slugify($name);
	$category = $_POST['category'];
	$price = $_POST['price'];
	$description = $_POST['description'];
	$size = isset($_POST['size']) ? $_POST['size'] : [];
	$size = !empty($size) ? implode(',', $size) : '';

	$material = isset($_POST['material']) ? $_POST['material'] : [];
	$material = !empty($material) ? implode(',', $material) : '';

	$color = isset($_POST['color']) ? $_POST['color'] : [];
	$color = !empty($color) ? implode(',', $color) : '';

	$brand = $_POST['brand'];
	$qty = $_POST['quantity'];

	// Assuming $pdo is your PDO connection object
	$conn = $pdo->open();

	try {
		// Fetch category name
		$sql = $conn->prepare("SELECT cat_slug FROM category WHERE id = :category_id");
		$sql->execute(['category_id' => $category]);
		$category_name = null;
		if ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
			$category_name = $row['cat_slug'];
		}

		// Update product
		$stmt = $conn->prepare("UPDATE products SET name = :name, slug = :slug, category_id = :category_id, category_name = :category_name, price = :price, description = :description, color = :color, size = :size, brand = :brand, material = :material, qty = :qty WHERE id = :id");
		$stmt->execute([
			'name' => $name,
			'slug' => $slug,
			'category_id' => $category,
			'category_name' => $category_name,
			'price' => $price,
			'description' => $description,
			'color' => $color,
			'size' => $size,
			'brand' => $brand,
			'material' => $material,
			'qty' => $qty,
			'id' => $id
		]);

		$_SESSION['success'] = 'Product updated successfully';
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up edit product form first';
}

header('location: products.php');
