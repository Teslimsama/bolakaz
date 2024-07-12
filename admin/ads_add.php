<?php
include 'session.php';

if (isset($_POST['add'])) {
	$text_align = htmlspecialchars($_POST['text_align']);
	$discount = htmlspecialchars($_POST['discount']);
	$category_id = htmlspecialchars($_POST['category_id']);

	$conn = $pdo->open();

	// Check if the category exists and fetch the slug
	$stmt = $conn->prepare("SELECT cat_slug, name FROM category WHERE id=:id");
	$stmt->execute(['id' => $category_id]);
	$category = $stmt->fetch();

	if ($category) {
		$link = $category['cat_slug'];
		$collection = $category['name'];

		// Handle file upload
		$target_dir = "../images/";
		$target_file = $target_dir . basename($_FILES["image_path"]["name"]);
		$uploadOk = 1;
		$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

		// Check if image file is an actual image or fake image
		$check = getimagesize($_FILES["image_path"]["tmp_name"]);
		if ($check !== false) {
			$uploadOk = 1;
		} else {
			$_SESSION['error'] = "File is not an image.";
			$uploadOk = 0;
		}

		// Check if file already exists
		if (file_exists($target_file)) {
			$_SESSION['error'] = "Sorry, file already exists.";
			$uploadOk = 0;
		}

		// Check file size
		if ($_FILES["image_path"]["size"] > 5000000) { // 5MB limit
			$_SESSION['error'] = "Sorry, your file is too large.";
			$uploadOk = 0;
		}

		// Allow certain file formats
		if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
			$_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
			$uploadOk = 0;
		}

		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 0) {
			$_SESSION['error'] = "Sorry, your file was not uploaded.";
		} else {
			if (move_uploaded_file($_FILES["image_path"]["tmp_name"], $target_file)) {
				$image_path = htmlspecialchars(basename($_FILES["image_path"]["name"]));
				try {
					$stmt = $conn->prepare("INSERT INTO ads (text_align, image_path, discount, collection, link) VALUES (:text_align, :image_path, :discount, :collection, :link)");
					$stmt->execute(['text_align' => $text_align, 'image_path' => $image_path, 'discount' => $discount, 'collection' => $collection, 'link' => $link]);
					$_SESSION['success'] = 'Ad added successfully';
				} catch (PDOException $e) {
					$_SESSION['error'] = $e->getMessage();
				}
			} else {
				$_SESSION['error'] = "Sorry, there was an error uploading your file.";
			}
		}
	} else {
		$_SESSION['error'] = 'Invalid category selected';
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up ad form first';
}
header('location: ads.php');
