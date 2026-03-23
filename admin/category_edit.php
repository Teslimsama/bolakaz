<?php
include 'session.php';
include 'slugify.php';
require_once __DIR__ . '/../lib/image_tools.php';

$redirectUrl = 'category';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = slugify($name);
    $isParent = isset($_POST['is_parent']) ? 1 : 0;
    $parentId = $isParent ? null : (int)($_POST['parent_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? 'active'));

    if ($id <= 0 || $name === '' || $slug === '' || !in_array($status, ['active', 'inactive'], true)) {
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
    $newImage = app_store_uploaded_image($_FILES['cat-image'] ?? [], [
        'required' => false,
        'field_label' => 'Category image',
        'upload_dir' => __DIR__ . '/../images',
        'filename_prefix' => 'cat_',
    ], $uploadError);
    if ($newImage === null) {
        $_SESSION['error'] = $uploadError;
        header('location: ' . $redirectUrl);
        exit;
    }

    $conn = $pdo->open();
    try {
        $currentStmt = $conn->prepare("SELECT cat_image FROM category WHERE id=:id LIMIT 1");
        $currentStmt->execute(['id' => $id]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            $_SESSION['error'] = 'Category not found';
            if ($newImage !== '') {
                @unlink(__DIR__ . '/../images/' . $newImage);
            }
            header('location: ' . $redirectUrl);
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
        if ($newImage !== '') {
            @unlink(__DIR__ . '/../images/' . $newImage);
        }
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: ' . $redirectUrl);
