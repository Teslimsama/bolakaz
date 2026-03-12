<?php
include 'session.php';

function ads_upload_image(array $file, string &$error = ''): ?string
{
    if (empty($file['name']) || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        $error = 'Please select an image.';
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
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        $error = 'Sorry, only JPG, JPEG, PNG, GIF & WEBP files are allowed.';
        return null;
    }

    $filename = uniqid('ad_', true) . '.' . $ext;
    $target = '../images/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        $error = 'Sorry, there was an error uploading your file.';
        return null;
    }

    return $filename;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $textAlign = trim((string)($_POST['text_align'] ?? ''));
    $discount = trim((string)($_POST['discount'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (!in_array($textAlign, ['text-md-right', 'text-md-left', 'text-md-center'], true) || $discount === '' || $categoryId <= 0) {
        $_SESSION['error'] = 'Please provide valid ad details.';
        header('location: ads.php');
        exit;
    }

    $conn = $pdo->open();

    try {
        $stmt = $conn->prepare("SELECT cat_slug, name FROM category WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $_SESSION['error'] = 'Invalid category selected';
            header('location: ads.php');
            exit;
        }

        $uploadError = '';
        $imagePath = ads_upload_image($_FILES['image_path'] ?? [], $uploadError);
        if ($imagePath === null) {
            $_SESSION['error'] = $uploadError;
            header('location: ads.php');
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
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: ads.php');
