<?php
include 'session.php';
require_once __DIR__ . '/../lib/banner_links.php';
require_once __DIR__ . '/../lib/image_tools.php';

$redirectUrl = 'banner';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $captionHeading = trim((string)($_POST['caption_heading'] ?? ''));
    $captionText = trim((string)($_POST['caption_text'] ?? ''));

    if ($id <= 0 || $name === '' || $captionHeading === '' || $captionText === '') {
        $_SESSION['error'] = 'Please provide valid banner details.';
        header('location: ' . $redirectUrl);
        exit;
    }

    $uploadError = '';
    $newImage = app_store_uploaded_image($_FILES['banner_image'] ?? [], [
        'required' => false,
        'field_label' => 'Banner image',
        'upload_dir' => __DIR__ . '/../images',
        'filename_prefix' => 'banner_',
    ], $uploadError);
    if ($newImage === null) {
        $_SESSION['error'] = $uploadError;
        header('location: ' . $redirectUrl);
        exit;
    }

    $conn = $pdo->open();
    try {
        $destinationError = '';
        $link = banner_build_link_from_request($conn, $_POST, $destinationError);
        if ($link === null) {
            $_SESSION['error'] = $destinationError;
            if ($newImage !== '') {
                @unlink(__DIR__ . '/../images/' . $newImage);
            }
            header('location: ' . $redirectUrl);
            exit;
        }

        $currentStmt = $conn->prepare("SELECT image_path FROM banner WHERE id = :id LIMIT 1");
        $currentStmt->execute(['id' => $id]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            $_SESSION['error'] = 'Banner item not found.';
            if ($newImage !== '') {
                @unlink(__DIR__ . '/../images/' . $newImage);
            }
            header('location: ' . $redirectUrl);
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
