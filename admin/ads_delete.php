<?php
include 'session.php';
require_once __DIR__ . '/../lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
	$id = (int)($_POST['id'] ?? 0);
	if ($id <= 0) {
		$_SESSION['error'] = 'Invalid ad selected';
		header('location: ads.php');
		exit;
	}

	$conn = $pdo->open();
    $imagePath = '';

	try {
		$stmt = $conn->prepare("SELECT * FROM ads WHERE id=:id LIMIT 1");
		$stmt->execute(['id' => $id]);
		$ad = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$ad) {
			$_SESSION['error'] = 'Ad not found';
			$pdo->close();
			header('location: ads.php');
			exit;
		}

		$conn->beginTransaction();
		sync_enqueue_delete_or_fail($conn, 'ads', $ad);
		$stmt = $conn->prepare("DELETE FROM ads WHERE id=:id");
		$stmt->execute(['id' => $id]);
        $conn->commit();
        $imagePath = __DIR__ . '/../images/' . ltrim((string)($ad['image_path'] ?? ''), '/');

		$_SESSION['success'] = 'Ad deleted successfully';
	} catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
		$_SESSION['error'] = 'Unable to delete ad.';
	}

	$pdo->close();
    if ($imagePath !== '' && is_file($imagePath)) {
        @unlink($imagePath);
    }
} else {
	$_SESSION['error'] = 'Invalid request method';
}
header('location: ads.php');
