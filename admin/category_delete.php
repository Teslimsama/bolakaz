<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid category selected';
        header('location: category.php');
        exit;
    }

    $conn = $pdo->open();
    $imagePath = '';
    try {
        $stmt = $conn->prepare("SELECT * FROM category WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $_SESSION['error'] = 'Category not found';
            header('location: category.php');
            exit;
        }

        $conn->beginTransaction();
        sync_enqueue_delete_or_fail($conn, 'category', $category);
        $delete = $conn->prepare("DELETE FROM category WHERE id=:id");
        $delete->execute(['id' => $id]);
        $conn->commit();
        $imagePath = __DIR__ . '/../images/' . ltrim((string)($category['cat_image'] ?? ''), '/');

        $_SESSION['success'] = 'Category deleted successfully';
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $_SESSION['error'] = 'Unable to delete category';
    } finally {
        $pdo->close();
    }

    if ($imagePath !== '' && is_file($imagePath)) {
        @unlink($imagePath);
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: category.php');
