<?php
include 'session.php';
require_once __DIR__ . '/../lib/banner_links.php';

function banner_upload_image(array $file, string &$error = ''): ?string
{
    if (empty($file['name']) || !is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
        $error = 'Please upload an image for the banner.';
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

    $filename = uniqid('banner_', true) . '.' . $ext;
    $target = '../images/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        $error = 'Failed to upload image.';
        return null;
    }

    return $filename;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $captionHeading = trim((string)($_POST['caption_heading'] ?? ''));
    $captionText = trim((string)($_POST['caption_text'] ?? ''));

    if ($name === '' || $captionHeading === '' || $captionText === '') {
        $_SESSION['error'] = 'Please fill all banner fields.';
        header('location: banner.php');
        exit;
    }

    $uploadError = '';
    $imagePath = banner_upload_image($_FILES['banner_image'] ?? [], $uploadError);
    if ($imagePath === null) {
        $_SESSION['error'] = $uploadError;
        header('location: banner.php');
        exit;
    }

    $conn = $pdo->open();
    try {
        $destinationError = '';
        $link = banner_build_link_from_request($conn, $_POST, $destinationError);
        if ($link === null) {
            $_SESSION['error'] = $destinationError;
            header('location: banner.php');
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO banner (name, image_path, caption_heading, caption_text, link)
            VALUES (:name, :image_path, :caption_heading, :caption_text, :link)");
        $stmt->execute([
            'name' => $name,
            'image_path' => $imagePath,
            'caption_heading' => $captionHeading,
            'caption_text' => $captionText,
            'link' => $link,
        ]);
        $_SESSION['success'] = 'Banner item added successfully';
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to add banner item.';
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: banner.php');
