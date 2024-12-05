<?php
include 'session.php';

if (isset($_POST['delete'])) {
	$id = $_POST['id'];

	$conn = $pdo->open();

	try {
		// Fetch the image path
		$stmt = $conn->prepare("SELECT cat_image FROM category WHERE id=:id");
		$stmt->execute(['id' => $id]);
		$category = $stmt->fetch();

		if ($category) {
			// Delete the image file from the server
			$imagePath = '../images/' . $category['cat_image'];
			if (file_exists($imagePath)) {
				unlink($imagePath);
			}

			// Delete the category record from the database
			$stmt = $conn->prepare("DELETE FROM category WHERE id=:id");
			$stmt->execute(['id' => $id]);

			$_SESSION['success'] = 'Category deleted successfully';
		} else {
			$_SESSION['error'] = 'Category not found';
		}
	} catch (PDOException $e) {
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Select category to delete first';
}

header('location: category.php');
