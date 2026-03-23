<?php
include 'session.php';
require_once __DIR__ . '/../lib/image_tools.php';
require_once __DIR__ . '/../lib/sync.php';

$redirectUrl = 'ads';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $textAlign = trim((string)($_POST['text_align'] ?? ''));
    $discount = trim((string)($_POST['discount'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if ($id <= 0 || !in_array($textAlign, ['text-md-right', 'text-md-left', 'text-md-center'], true) || $discount === '' || $categoryId <= 0) {
        $_SESSION['error'] = 'Please provide valid ad details.';
        header('location: ' . $redirectUrl);
        exit;
    }

    $conn = $pdo->open();
    $imagePath = '';
    $oldImagePathToDelete = '';

    try {
        $stmt = $conn->prepare("SELECT cat_slug, name FROM category WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $_SESSION['error'] = 'Invalid category selected';
            header('location: ' . $redirectUrl);
            exit;
        }

        $currentStmt = $conn->prepare("SELECT image_path FROM ads WHERE id=:id LIMIT 1");
        $currentStmt->execute(['id' => $id]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            $_SESSION['error'] = 'Ad not found.';
            header('location: ' . $redirectUrl);
            exit;
        }
        $oldImage = (string)($current['image_path'] ?? '');

        $uploadError = '';
        $imagePath = app_store_uploaded_image($_FILES['image_path'] ?? [], [
            'required' => false,
            'field_label' => 'Ad image',
            'upload_dir' => __DIR__ . '/../images',
            'filename_prefix' => 'ad_',
        ], $uploadError);
        if ($imagePath === null) {
            $_SESSION['error'] = $uploadError;
            header('location: ' . $redirectUrl);
            exit;
        }

        $conn->beginTransaction();
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
        sync_enqueue_or_fail($conn, 'ads', $id);
        $conn->commit();

        if ($imagePath !== '' && $oldImage !== '' && $oldImage !== $imagePath) {
            $oldImagePathToDelete = __DIR__ . '/../images/' . ltrim($oldImage, '/');
        }

        $_SESSION['success'] = 'Ad updated successfully';
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = 'Unable to update ad.';
        if ($imagePath !== '') {
            @unlink(__DIR__ . '/../images/' . $imagePath);
        }
    } finally {
        $pdo->close();
    }

    if ($oldImagePathToDelete !== '' && is_file($oldImagePathToDelete)) {
        @unlink($oldImagePathToDelete);
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: ' . $redirectUrl);
