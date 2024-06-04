<?php
include 'session.php';

if (isset($_POST['delete'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	try {
		// Fetch the image path
		$stmt = $conn->prepare("SELECT image_path FROM banner WHERE id=:id");
		$stmt->execute(['id' => $id]);
		$banner = $stmt->fetch();

		if ($banner) {
			// Delete the image file from the server
			$imagePath = '../images/' . $banner['image_path'];
			if (file_exists($imagePath)) {
				unlink($imagePath);
			}

			// Delete the banner record from the database
			$stmt = $conn->prepare("DELETE FROM banner WHERE id=:id");
			$stmt->execute(['id' => $id]);

			$_SESSION['success'] = 'Banner item deleted successfully';
		} else {
			$_SESSION['error'] = 'Banner item not found';
		}
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Select banner item to delete first';
}

header('location: banner.php');
