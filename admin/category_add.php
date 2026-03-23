<?php
include 'session.php';
include 'slugify.php';
require_once __DIR__ . '/../lib/image_tools.php';

$redirectUrl = 'category';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = slugify($name);
    $isParent = isset($_POST['is_parent']) ? 1 : 0;
    $parentId = $isParent ? null : (int)($_POST['parent_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'active'));

    if ($name === '' || $slug === '' || !in_array($status, ['active', 'inactive'], true)) {
        $_SESSION['error'] = 'Please provide valid category details';
        header('location: ' . $redirectUrl);
        exit;
    }

    if (!$isParent && $parentId <= 0) {
        $_SESSION['error'] = 'Please select a parent category';
        header('location: ' . $redirectUrl);
        exit;
    }

    $uploadError = '';
    $catImage = app_store_uploaded_image($_FILES['cat-image'] ?? [], [
        'required' => true,
        'field_label' => 'Category image',
        'upload_dir' => __DIR__ . '/../images',
        'filename_prefix' => 'cat_',
    ], $uploadError);
    if ($catImage === null) {
        $_SESSION['error'] = $uploadError;
        header('location: ' . $redirectUrl);
        exit;
    }

    $conn = $pdo->open();
    try {
        $exists = $conn->prepare("SELECT COUNT(*) AS numrows FROM category WHERE name = :name");
        $exists->execute(['name' => $name]);
        $row = $exists->fetch(PDO::FETCH_ASSOC);

        if ((int)($row['numrows'] ?? 0) > 0) {
            $_SESSION['error'] = 'Category already exists';
            @unlink(__DIR__ . '/../images/' . $catImage);
            header('location: ' . $redirectUrl);
            exit;
        }

        $insert = $conn->prepare("INSERT INTO category (name, cat_slug, cat_image, is_parent, parent_id, status)
            VALUES (:name, :cat_slug, :cat_image, :is_parent, :parent_id, :status)");
        $insert->execute([
            'name' => $name,
            'cat_slug' => $slug,
            'cat_image' => $catImage,
            'is_parent' => $isParent,
            'parent_id' => $isParent ? null : $parentId,
            'status' => $status,
        ]);

        $_SESSION['success'] = 'Category added successfully';
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to add category';
        if ($catImage !== '') {
            @unlink(__DIR__ . '/../images/' . $catImage);
        }
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: ' . $redirectUrl);
