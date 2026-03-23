<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid banner item selected';
        header('location: banner.php');
        exit;
    }

    $conn = $pdo->open();
    $imagePath = '';

    try {
        $stmt = $conn->prepare("SELECT * FROM banner WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($banner) {
            $conn->beginTransaction();
            sync_enqueue_delete_or_fail($conn, 'banner', $banner);
            $delete = $conn->prepare("DELETE FROM banner WHERE id=:id");
            $delete->execute(['id' => $id]);
            $conn->commit();
            $imagePath = __DIR__ . '/../images/' . (string)$banner['image_path'];
            $_SESSION['success'] = 'Banner item deleted successfully';
        } else {
            $_SESSION['error'] = 'Banner item not found';
        }
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = 'Unable to delete banner item.';
    }

    $pdo->close();
    if ($imagePath !== '' && is_file($imagePath)) {
        @unlink($imagePath);
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: banner.php');
