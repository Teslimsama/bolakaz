<?php
include 'session.php';
include 'slugify.php';

function category_upload_image_optional(array $file, string &$error = ''): ?string
{
    if (empty($file['name']) || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        return '';
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
        $error = 'Error uploading image.';
        return null;
    }

    return $filename;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = slugify($name);
    $isParent = isset($_POST['is_parent']) ? 1 : 0;
    $parentId = $isParent ? null : (int)($_POST['parent_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'active'));

    if ($id <= 0 || $name === '' || $slug === '' || !in_array($status, ['active', 'inactive'], true)) {
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
    $newImage = category_upload_image_optional($_FILES['cat-image'] ?? [], $uploadError);
    if ($newImage === null) {
        $_SESSION['error'] = $uploadError;
        header('location: category.php');
        exit;
    }

    $conn = $pdo->open();
    try {
        $currentStmt = $conn->prepare("SELECT cat_image FROM category WHERE id=:id LIMIT 1");
        $currentStmt->execute(['id' => $id]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            $_SESSION['error'] = 'Category not found';
            header('location: category.php');
            exit;
        }
        $oldImage = (string)($current['cat_image'] ?? '');

        $sql = "UPDATE category
            SET name=:name, cat_slug=:cat_slug, is_parent=:is_parent, parent_id=:parent_id, status=:status";
        $params = [
            'name' => $name,
            'cat_slug' => $slug,
            'is_parent' => $isParent,
            'parent_id' => $isParent ? null : $parentId,
            'status' => $status,
            'id' => $id,
        ];

        if ($newImage !== '') {
            $sql .= ", cat_image=:cat_image";
            $params['cat_image'] = $newImage;
        }

        $sql .= " WHERE id=:id";
        $update = $conn->prepare($sql);
        $update->execute($params);

        if ($newImage !== '' && $oldImage !== '' && $oldImage !== $newImage) {
            $oldPath = '../images/' . ltrim($oldImage, '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $_SESSION['success'] = 'Category updated successfully';
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to update category';
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: category.php');
