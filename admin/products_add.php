<?php
include 'session.php';
include 'slugify.php';
// require '../vendor/autoload.php'; // Load Intervention Image
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Intervention\Image\ImageManagerStatic as Image;

if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $slug = slugify($name);
    $category = $_POST['category'];
    $subcategory = $_POST['child_cat_id'];
    $category_name = $_POST['category_name'];
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
    $product_status = 1;

    // File upload settings
    $uploadDir = '../images/';
    $allowTypes = ['jpg', 'jpeg', 'png', 'gif'];
    $errorUpload = '';

    $conn = $pdo->open();

    // Check if product already exists
    $stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM products WHERE slug=:slug");
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();

    if ($row['numrows'] < 1) {
        // Handle product photo
        $productPhoto = '';
        if (!empty($_FILES['photo']['name'])) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $productPhoto = $slug . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $productPhoto);
        }

        try {
            // Insert product data into the database
            $stmt = $conn->prepare(
				"INSERT INTO products (category_id, subcategory_id, category_name, name, description, slug, price, color, size, brand, material, qty, photo, product_status) 
                 VALUES (:category, :subcategory, :category_name, :name, :description, :slug, :price, :color, :size, :brand, :material, :qty, :photo, :product_status)"
            );
            $stmt->execute([
                'category' => $category,
                'subcategory' => $subcategory,
                'category_name' => $category_name,
                'name' => $name,
                'description' => $description,
                'slug' => $slug,
                'price' => $price,
                'color' => $color,
                'size' => $size,
                'brand' => $brand,
                'material' => $material,
                'qty' => $qty,
                'photo' => $productPhoto,
                'product_status' => $product_status,
            ]);

            // Get the last inserted product ID
            $productID = $conn->lastInsertId();
			
            // Handle additional gallery images
            $fileImages = array_filter($_FILES['images']['name']);
            if (!empty($fileImages)) {
				foreach ($fileImages as $key => $val) {
					$productID = $conn->lastInsertId();
                    $fileExtension = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                    $newFileName = $slug . '_' . $productID .  '_' . uniqid() . '.' . $fileExtension;
                    $targetFilePath = $uploadDir . $newFileName;

                    if (in_array($fileExtension, $allowTypes)) {
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFilePath)) {
                            try {
                                // Process the image with Intervention
                                $image = Image::make($targetFilePath);

                                // Correct orientation and resize
                                $image->orientate();
                                $image->resize(800, null, function ($constraint) {
                                    $constraint->aspectRatio();
                                    $constraint->upsize();
                                })->save($targetFilePath, 75);

                                // Insert image metadata into the database
                                $stmt = $conn->prepare(
                                    "INSERT INTO gallery_images (gallery_id, file_name) VALUES (:gallery_id, :file_name)"
                                );
                                $stmt->execute([
                                    'gallery_id' => $productID,
                                    'file_name' => $newFileName,
                                ]);
                            } catch (\Exception $e) {
                                $errorUpload .= "$val | Image processing failed: " . $e->getMessage() . " ";
                                @unlink($targetFilePath); // Remove invalid image
                            }
                        } else {
                            $errorUpload .= "$val | Upload failed. ";
                        }
                    } else {
                        $errorUpload .= "$val | Invalid file type. ";
                    }
                }
            }

            if (!empty($errorUpload)) {
                $_SESSION['error'] = 'Some issues occurred: ' . $errorUpload;
            } else {
                $_SESSION['success'] = 'Product added successfully, including gallery images.';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Product already exists.';
    }

    $pdo->close();
} else {
    $_SESSION['error'] = 'Fill up the product form first.';
}

header('location: products');
?>
