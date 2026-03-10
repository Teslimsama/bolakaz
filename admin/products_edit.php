<?php
include 'session.php';
include 'slugify.php';
require_once __DIR__ . '/../lib/product_payload.php';

if (!isset($_POST['edit'])) {
    $_SESSION['error'] = 'Fill up edit product form first';
    header('location: products.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$category = (int)($_POST['category'] ?? 0);
$subcategory = (int)($_POST['edit_child_cat_id'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$description = trim((string)($_POST['description'] ?? ''));
$brand = trim((string)($_POST['brand'] ?? ''));
$qty = (int)($_POST['quantity'] ?? 0);
$productStatus = (int)($_POST['product_status'] ?? 1);

$sizeValues = product_normalize_values($_POST['size'] ?? []);
$colorValues = product_normalize_values($_POST['color'] ?? []);
$materialValues = product_normalize_values($_POST['material'] ?? [], 80);
$specs = product_collect_specs($_POST, 'edit_');
$additionalInfo = product_encode_specs($specs);

$errors = [];
if ($id <= 0) {
    $errors[] = 'Invalid product id.';
}
if ($name === '') {
    $errors[] = 'Product name is required.';
}
if ($category <= 0) {
    $errors[] = 'Category is required.';
}
if ($price <= 0) {
    $errors[] = 'Price must be greater than zero.';
}
if ($description === '') {
    $errors[] = 'Description is required.';
}
if ($brand === '') {
    $errors[] = 'Brand is required.';
}
if ($qty < 0) {
    $errors[] = 'Quantity cannot be negative.';
}
if (empty($materialValues)) {
    $errors[] = 'Select at least one material.';
}
if ($productStatus !== 0 && $productStatus !== 1) {
    $errors[] = 'Invalid product status.';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode(' ', $errors);
    header('location: products.php');
    exit();
}

$slugBase = slugify($name);
if ($slugBase === '') {
    $slugBase = 'product';
}

$conn = $pdo->open();

try {
    $catStmt = $conn->prepare("SELECT cat_slug FROM category WHERE id = :id LIMIT 1");
    $catStmt->execute(['id' => $category]);
    $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
    $categoryName = (string)($catRow['cat_slug'] ?? '');
    if ($categoryName === '') {
        $_SESSION['error'] = 'Selected category is invalid.';
        header('location: products.php');
        exit();
    }

    $slug = $slugBase;
    $counter = 1;
    $slugStmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM products WHERE slug=:slug AND id <> :id");
    while (true) {
        $slugStmt->execute(['slug' => $slug, 'id' => $id]);
        $exists = (int)($slugStmt->fetch(PDO::FETCH_ASSOC)['numrows'] ?? 0);
        if ($exists === 0) {
            break;
        }
        $slug = $slugBase . '-' . $counter;
        $counter++;
    }

    $stmt = $conn->prepare(
        "UPDATE products
         SET name = :name,
             slug = :slug,
             category_id = :category_id,
             subcategory_id = :subcategory_id,
             category_name = :category_name,
             price = :price,
             description = :description,
             additional_info = :additional_info,
             color = :color,
             size = :size,
             brand = :brand,
             material = :material,
             qty = :qty,
             product_status = :product_status
         WHERE id = :id"
    );
    $stmt->execute([
        'name' => $name,
        'slug' => $slug,
        'category_id' => $category,
        'subcategory_id' => ($subcategory > 0 ? $subcategory : null),
        'category_name' => $categoryName,
        'price' => $price,
        'description' => $description,
        'additional_info' => ($additionalInfo !== '' ? $additionalInfo : null),
        'color' => product_values_to_csv($colorValues),
        'size' => product_values_to_csv($sizeValues),
        'brand' => $brand,
        'material' => product_values_to_csv($materialValues),
        'qty' => $qty,
        'product_status' => $productStatus,
        'id' => $id,
    ]);

    $_SESSION['success'] = 'Product updated successfully';
} catch (Throwable $e) {
    error_log('Product edit failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to update product right now.';
}

$pdo->close();
header('location: products.php');
exit();
