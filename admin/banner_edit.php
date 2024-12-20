<?php
include 'session.php';

if (isset($_POST['edit'])) {
	$id = $_POST['id'];
	$name = htmlspecialchars($_POST['name']); // Sanitize input data
	$link = htmlspecialchars($_POST['link']); // Sanitize input data
	$caption_text = htmlspecialchars($_POST['caption_text']); // Sanitize input data
	$caption_heading = htmlspecialchars($_POST['caption_heading']); // Sanitize input data

	// Handle banner image upload
	if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
		$target_dir = "../images/"; // Directory where the image will be uploaded
		$target_file = $target_dir . basename($_FILES["banner_image"]["name"]);
		$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

		// Check if the file is an actual image or fake image
		$check = getimagesize($_FILES["banner_image"]["tmp_name"]);
		if ($check !== false) {
			// Allow certain file formats
			if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif" || $imageFileType == "webp") {
				// Move uploaded file to destination directory
				move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_file);
				$banner_image = basename($_FILES["banner_image"]["name"]); // Save just the file name

				// Update banner item with new image path
				try {
					$stmt = $conn->prepare("UPDATE banner SET name=:name, caption_text=:caption_text, caption_heading=:caption_heading, link=:link, image_path=:banner_image WHERE id=:id");
					$stmt->execute(['name' => $name, 'caption_text' => $caption_text, 'caption_heading' => $caption_heading, 'banner_image' => $banner_image, 'link' => $link, 'id' => $id]);
					$_SESSION['success'] = 'banner item updated successfully';
				} catch (PDOException $e) {
					$_SESSION['error'] = $e->getMessage();
				}
			} else {
				$_SESSION['error'] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
			}
		} else {
			$_SESSION['error'] = 'File is not an image.';
		}
	} else {
		// Update banner item without changing the image
		try {
			$stmt = $conn->prepare("UPDATE banner SET name=:name, caption_text=:caption_text, link=:link, caption_heading=:caption_heading  WHERE id=:id");
			$stmt->execute(['name' => $name, 'caption_text' => $caption_text, 'caption_heading' => $caption_heading, 'link' => $link, 'id' => $id]);
			$_SESSION['success'] = 'banner item updated successfully';
		} catch (PDOException $e) {
			$_SESSION['error'] = $e->getMessage();
		}
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up edit banner form first';
}

header('location: banner.php');
