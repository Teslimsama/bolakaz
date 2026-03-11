<?php
include 'session.php';
require_once __DIR__ . '/../lib/banner_links.php';

function banner_upload_image_optional(array $file, string &$error = ''): ?string
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

    $filename = uniqid('banner_', true) . '.' . $ext;
    $target = '../images/' . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        $error = 'Failed to upload image.';
        return null;
    }

    return $filename;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $captionHeading = trim((string)($_POST['caption_heading'] ?? ''));
    $captionText = trim((string)($_POST['caption_text'] ?? ''));

    if ($id <= 0 || $name === '' || $captionHeading === '' || $captionText === '') {
        $_SESSION['error'] = 'Please provide valid banner details.';
        header('location: banner.php');
        exit;
    }

    $uploadError = '';
    $newImage = banner_upload_image_optional($_FILES['banner_image'] ?? [], $uploadError);
    if ($newImage === null) {
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

        $currentStmt = $conn->prepare("SELECT image_path FROM banner WHERE id = :id LIMIT 1");
        $currentStmt->execute(['id' => $id]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            $_SESSION['error'] = 'Banner item not found.';
            header('location: banner.php');
            exit;
        }
        $oldImage = (string)($current['image_path'] ?? '');

        $sql = "UPDATE banner SET name=:name, caption_text=:caption_text, caption_heading=:caption_heading, link=:link";
        $params = [
            'name' => $name,
            'caption_text' => $captionText,
            'caption_heading' => $captionHeading,
            'link' => $link,
            'id' => $id,
        ];

        if ($newImage !== '') {
            $sql .= ", image_path=:image_path";
            $params['image_path'] = $newImage;
        }

        $sql .= " WHERE id=:id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        if ($newImage !== '' && $oldImage !== '' && $oldImage !== $newImage) {
            $oldPath = '../images/' . ltrim($oldImage, '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $_SESSION['success'] = 'Banner item updated successfully';
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to update banner item.';
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: banner.php');
