<?php
include 'session.php';
require_once __DIR__ . '/../lib/image_tools.php';

$redirectUrl = 'ads';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $textAlign = trim((string)($_POST['text_align'] ?? ''));
    $discount = trim((string)($_POST['discount'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (!in_array($textAlign, ['text-md-right', 'text-md-left', 'text-md-center'], true) || $discount === '' || $categoryId <= 0) {
        $_SESSION['error'] = 'Please provide valid ad details.';
        header('location: ' . $redirectUrl);
        exit;
    }

    $conn = $pdo->open();
    $imagePath = '';

    try {
        $stmt = $conn->prepare("SELECT cat_slug, name FROM category WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $_SESSION['error'] = 'Invalid category selected';
            header('location: ' . $redirectUrl);
            exit;
        }

        $uploadError = '';
        $imagePath = app_store_uploaded_image($_FILES['image_path'] ?? [], [
            'required' => true,
            'field_label' => 'Ad image',
            'upload_dir' => __DIR__ . '/../images',
            'filename_prefix' => 'ad_',
        ], $uploadError);
        if ($imagePath === null) {
            $_SESSION['error'] = $uploadError;
            header('location: ' . $redirectUrl);
            exit;
        }

        $insert = $conn->prepare("INSERT INTO ads (text_align, image_path, discount, collection, link)
            VALUES (:text_align, :image_path, :discount, :collection, :link)");
        $insert->execute([
            'text_align' => $textAlign,
            'image_path' => $imagePath,
            'discount' => $discount,
            'collection' => (string)$category['name'],
            'link' => (string)$category['cat_slug'],
        ]);

        $_SESSION['success'] = 'Ad added successfully';
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to add ad.';
        if ($imagePath !== '') {
            @unlink(__DIR__ . '/../images/' . $imagePath);
        }
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: ' . $redirectUrl);
