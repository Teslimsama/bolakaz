<?php
include 'session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid category selected';
        header('location: category.php');
        exit;
    }

    $conn = $pdo->open();
    try {
        $stmt = $conn->prepare("SELECT cat_image FROM category WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $_SESSION['error'] = 'Category not found';
            header('location: category.php');
            exit;
        }

        $imagePath = '../images/' . ltrim((string)($category['cat_image'] ?? ''), '/');
        if (is_file($imagePath)) {
            @unlink($imagePath);
        }

        $delete = $conn->prepare("DELETE FROM category WHERE id=:id");
        $delete->execute(['id' => $id]);

        $_SESSION['success'] = 'Category deleted successfully';
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to delete category';
    } finally {
        $pdo->close();
    }
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: category.php');
