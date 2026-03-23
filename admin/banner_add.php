<?php
include 'session.php';
require_once __DIR__ . '/../lib/banner_links.php';
require_once __DIR__ . '/../lib/image_tools.php';
require_once __DIR__ . '/../lib/sync.php';

$redirectUrl = 'banner';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $captionHeading = trim((string)($_POST['caption_heading'] ?? ''));
    $captionText = trim((string)($_POST['caption_text'] ?? ''));

    if ($name === '' || $captionHeading === '' || $captionText === '') {
        $_SESSION['error'] = 'Please fill all banner fields.';
        header('location: ' . $redirectUrl);
        exit;
    }

    $uploadError = '';
    $imagePath = app_store_uploaded_image($_FILES['banner_image'] ?? [], [
        'required' => true,
        'field_label' => 'Banner image',
        'upload_dir' => __DIR__ . '/../images',
        'filename_prefix' => 'banner_',
    ], $uploadError);
    if ($imagePath === null) {
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
            @unlink(__DIR__ . '/../images/' . $imagePath);
            header('location: ' . $redirectUrl);
            exit;
        }

        $conn->beginTransaction();
        $stmt = $conn->prepare("INSERT INTO banner (name, image_path, caption_heading, caption_text, link)
            VALUES (:name, :image_path, :caption_heading, :caption_text, :link)");
        $stmt->execute([
            'name' => $name,
            'image_path' => $imagePath,
            'caption_heading' => $captionHeading,
            'caption_text' => $captionText,
            'link' => $link,
        ]);
        $bannerId = (int) $conn->lastInsertId();
        sync_enqueue_or_fail($conn, 'banner', $bannerId);
        $conn->commit();
        $_SESSION['success'] = 'Banner item added successfully';
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = 'Unable to add banner item.';
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
