<?php
include 'session.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['error'] = 'Invalid banner item selected';
        header('location: banner.php');
        exit;
    }

    $conn = $pdo->open();

    try {
        $stmt = $conn->prepare("SELECT image_path FROM banner WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($banner) {
            $imagePath = '../images/' . (string)$banner['image_path'];
            if (is_file($imagePath)) {
                @unlink($imagePath);
            }

            $delete = $conn->prepare("DELETE FROM banner WHERE id=:id");
            $delete->execute(['id' => $id]);
            $_SESSION['success'] = 'Banner item deleted successfully';
        } else {
            $_SESSION['error'] = 'Banner item not found';
        }
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Unable to delete banner item.';
    }

    $pdo->close();
} else {
    $_SESSION['error'] = 'Invalid request method';
}

header('location: banner.php');
