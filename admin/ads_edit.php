<?php
include 'session.php';

function ads_upload_image_optional(array $file, string &$error = ''): ?string
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
    $id = (int)($_POST['id'] ?? 0);
    $textAlign = trim((string)($_POST['text_align'] ?? ''));
    $discount = trim((string)($_POST['discount'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if ($id <= 0 || !in_array($textAlign, ['text-md-right', 'text-md-left', 'text-md-center'], true) || $discount === '' || $categoryId <= 0) {
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
        $imagePath = ads_upload_image_optional($_FILES['image_path'] ?? [], $uploadError);
        if ($imagePath === null) {
            $_SESSION['error'] = $uploadError;
            header('location: ads.php');
            exit;
        }

        $sql = "UPDATE ads SET text_align=:text_align, discount=:discount, collection=:collection, link=:link";
        $params = [
            'text_align' => $textAlign,
            'discount' => $discount,
            'collection' => (string)$category['name'],
            'link' => (string)$category['cat_slug'],
            'id' => $id,
        ];

        if ($imagePath !== '') {
            $sql .= ", image_path=:image_path";
            $params['image_path'] = $imagePath;
        }

        $sql .= " WHERE id=:id";
        $update = $conn->prepare($sql);
        $update->execute($params);

        $_SESSION['success'] = 'Ad updated successfully';
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to update ad.';
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: ads.php');
