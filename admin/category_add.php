<?php
include 'session.php';
include 'slugify.php';

function category_upload_image(array $file, string &$error = ''): ?string
{
    if (empty($file['name']) || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        $error = 'Category image is required.';
        return null;
    }

    $tmp = (string)$file['tmp_name'];
    $check = @getimagesize($tmp);
    if ($check === false) {
        $error = 'File is not an image.';
        return null;
    }

    $size = (int)($file['size'] ?? 0);
    if ($size > 5 * 1024 * 1024) {
        $error = 'Sorry, your file is too large.';
        return null;
    }

    $ext = strtolower((string)pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $error = 'Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.';
        return null;
    }

    $filename = uniqid('cat_', true) . '.' . $ext;
    $target = '../images/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        $error = 'Error uploading category image.';
        return null;
    }

    return $filename;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = slugify($name);
    $isParent = isset($_POST['is_parent']) ? 1 : 0;
    $parentId = $isParent ? null : (int)($_POST['parent_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'active'));

    if ($name === '' || $slug === '' || !in_array($status, ['active', 'inactive'], true)) {
        $_SESSION['error'] = 'Please provide valid category details';
        header('location: category.php');
        exit;
    }

    if (!$isParent && $parentId <= 0) {
        $_SESSION['error'] = 'Please select a parent category';
        header('location: category.php');
        exit;
    }

    $uploadError = '';
    $catImage = category_upload_image($_FILES['cat-image'] ?? [], $uploadError);
    if ($catImage === null) {
        $_SESSION['error'] = $uploadError;
        header('location: category.php');
        exit;
    }

    $conn = $pdo->open();
    try {
        $exists = $conn->prepare("SELECT COUNT(*) AS numrows FROM category WHERE name = :name");
        $exists->execute(['name' => $name]);
        $row = $exists->fetch(PDO::FETCH_ASSOC);

        if ((int)($row['numrows'] ?? 0) > 0) {
            $_SESSION['error'] = 'Category already exists';
            header('location: category.php');
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
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: category.php');
