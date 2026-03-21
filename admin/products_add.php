<?php
include 'session.php';
include 'slugify.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/product_payload.php';
require_once __DIR__ . '/../lib/catalog_v2.php';

use Intervention\Image\ImageManagerStatic as Image;

if (!function_exists('admin_uploaded_image_is_valid')) {
    function admin_uploaded_image_is_valid(string $path, array $allowMime): bool
    {
        if ($path === '' || !is_file($path)) {
            return false;
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $path);
                finfo_close($finfo);
            }
        }

        if ($mime !== '' && !in_array($mime, $allowMime, true)) {
            return false;
        }

        return @getimagesize($path) !== false;
    }
}

if (!isset($_POST['add'])) {
    $_SESSION['error'] = 'Fill up the product form first.';
    header('location: products');
    exit();
}

$name = trim((string)($_POST['name'] ?? ''));
$category = (int)($_POST['category'] ?? 0);
$subcategory = (int)($_POST['child_cat_id'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$description = trim((string)($_POST['description'] ?? ''));
$brand = trim((string)($_POST['brand'] ?? ''));
$qty = (int)($_POST['quantity'] ?? 0);
$productStatus = (int)($_POST['product_status'] ?? 1);

$sizeValues = product_normalize_values($_POST['size'] ?? []);
$colorValues = product_normalize_values($_POST['color'] ?? []);
$materialValues = product_normalize_values($_POST['material'] ?? [], 80);
$customSizeValues = product_normalize_values(explode(',', (string)($_POST['custom_size_values'] ?? '')));
$customColorValues = product_normalize_values(explode(',', (string)($_POST['custom_color_values'] ?? '')));
$customMaterialValues = product_normalize_values(explode(',', (string)($_POST['custom_material_values'] ?? '')), 80);
$sizeValues = product_normalize_values(array_merge($sizeValues, $customSizeValues));
$colorValues = product_normalize_values(array_merge($colorValues, $customColorValues));
$materialValues = product_normalize_values(array_merge($materialValues, $customMaterialValues), 80);
$specs = product_collect_specs($_POST);
$additionalInfo = product_encode_specs($specs);

$errors = [];
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
    header('location: products');
    exit();
}

$slugBase = slugify($name);
if ($slugBase === '') {
    $slugBase = 'product';
}

$uploadDir = __DIR__ . '/../images/';
$allowTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$errorUpload = '';
$galleryOptimizationWarnings = [];

$conn = $pdo->open();

try {
    $catStmt = $conn->prepare("SELECT cat_slug FROM category WHERE id = :id LIMIT 1");
    $catStmt->execute(['id' => $category]);
    $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
    $categoryName = (string)($catRow['cat_slug'] ?? '');
    if ($categoryName === '') {
        $_SESSION['error'] = 'Selected category is invalid.';
        header('location: products');
        exit();
    }

    $slug = $slugBase;
    $counter = 1;
    $slugStmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM products WHERE slug=:slug");
    while (true) {
        $slugStmt->execute(['slug' => $slug]);
        $exists = (int)($slugStmt->fetch(PDO::FETCH_ASSOC)['numrows'] ?? 0);
        if ($exists === 0) {
            break;
        }
        $slug = $slugBase . '-' . $counter;
        $counter++;
    }

    $productPhoto = '';
    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower((string)pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowTypes, true)) {
            $_SESSION['error'] = 'Invalid main photo format.';
            header('location: products');
            exit();
        }
        $productPhoto = $slug . '.' . $ext;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $productPhoto)) {
            $_SESSION['error'] = 'Unable to upload main photo.';
            header('location: products');
            exit();
        }
    }

    $insert = $conn->prepare(
        "INSERT INTO products (category_id, subcategory_id, category_name, name, description, additional_info, slug, price, color, size, brand, material, qty, photo, product_status)
         VALUES (:category, :subcategory, :category_name, :name, :description, :additional_info, :slug, :price, :color, :size, :brand, :material, :qty, :photo, :product_status)"
    );
    $insert->execute([
        'category' => $category,
        'subcategory' => ($subcategory > 0 ? $subcategory : null),
        'category_name' => $categoryName,
        'name' => $name,
        'description' => $description,
        'additional_info' => ($additionalInfo !== '' ? $additionalInfo : null),
        'slug' => $slug,
        'price' => $price,
        'color' => product_values_to_csv($colorValues),
        'size' => product_values_to_csv($sizeValues),
        'brand' => $brand,
        'material' => product_values_to_csv($materialValues),
        'qty' => $qty,
        'photo' => $productPhoto,
        'product_status' => $productStatus,
    ]);

    $productID = (int)$conn->lastInsertId();

    $fileImages = isset($_FILES['images']['name']) && is_array($_FILES['images']['name']) ? array_filter($_FILES['images']['name']) : [];
    if (!empty($fileImages)) {
        foreach ($fileImages as $key => $val) {
            $fileExtension = strtolower((string)pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowTypes, true)) {
                $errorUpload .= $val . ' | Invalid file type. ';
                continue;
            }

            $newFileName = $slug . '_' . $productID . '_' . uniqid('', true) . '.' . $fileExtension;
            $targetFilePath = $uploadDir . $newFileName;
            if (!move_uploaded_file($_FILES['images']['tmp_name'][$key], $targetFilePath)) {
                $errorUpload .= $val . ' | Upload failed. ';
                continue;
            }

            $optimized = true;
            try {
                $image = Image::make($targetFilePath);
                $image->orientate();
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($targetFilePath, 80);
            } catch (Throwable $e) {
                if (!admin_uploaded_image_is_valid($targetFilePath, $allowMime)) {
                    $errorUpload .= $val . ' | Image processing failed. ';
                    @unlink($targetFilePath);
                    continue;
                }

                $optimized = false;
                if (function_exists('app_log')) {
                    app_log('warning', 'Gallery image optimization failed; falling back to original upload during product add.', [
                        'file' => $val,
                        'product_id' => $productID,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (!is_file($targetFilePath)) {
                $errorUpload .= $val . ' | Upload failed. ';
                continue;
            }

            $imgStmt = $conn->prepare(
                "INSERT INTO gallery_images (gallery_id, product_id, file_name, uploaded_on) VALUES (:gallery_id, :product_id, :file_name, :uploaded_on)"
            );
            $imgStmt->execute([
                'gallery_id' => $productID,
                'product_id' => $productID,
                'file_name' => $newFileName,
                'uploaded_on' => date('Y-m-d H:i:s'),
            ]);

            if (!$optimized) {
                $galleryOptimizationWarnings[] = $val;
            }
        }
    }

    $warningMessage = '';
    if (!empty($galleryOptimizationWarnings)) {
        $warningMessage = 'Some gallery images were uploaded without optimization due to server image-processing limits: ' . implode(', ', $galleryOptimizationWarnings) . '.';
    }

    if ($errorUpload !== '') {
        $_SESSION['error'] = 'Product added, but some gallery images failed: ' . $errorUpload;
    }
    $_SESSION['success'] = trim('Product added successfully. ' . $warningMessage);

    if (catalog_v2_table_exists($conn, 'products_v2')) {
        $syncSource = [
            'id' => $productID,
            'category_id' => $category,
            'subcategory_id' => ($subcategory > 0 ? $subcategory : null),
            'name' => $name,
            'description' => $description,
            'additional_info' => ($additionalInfo !== '' ? $additionalInfo : null),
            'slug' => $slug,
            'price' => $price,
            'color' => product_values_to_csv($colorValues),
            'size' => product_values_to_csv($sizeValues),
            'brand' => $brand,
            'material' => product_values_to_csv($materialValues),
            'qty' => $qty,
            'photo' => $productPhoto,
            'product_status' => $productStatus,
        ];
        catalog_v2_sync_product_from_legacy($conn, $syncSource);
    }
} catch (Throwable $e) {
    error_log('Product add failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to add product right now.';
}

$pdo->close();
header('location: products');
exit();
