<?php
include 'session.php';

if (isset($_POST['add'])) {
	$name = htmlspecialchars($_POST['name']);
	$caption_heading = htmlspecialchars($_POST['caption_heading']);
	$caption_text = htmlspecialchars($_POST['caption_text']);
	$link = htmlspecialchars($_POST['link']);

	// Handle file upload
	if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
		$target_dir = "../images/";
		$target_file = $target_dir . basename($_FILES["banner_image"]["name"]);
		$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

		// Check if the file is an actual image
		$check = getimagesize($_FILES["banner_image"]["tmp_name"]);
		if ($check !== false) {
			// Allow certain file formats
			if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
				if (move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_file)) {
					$image_path = basename($_FILES["banner_image"]["name"]);

					$conn = $pdo->open();

					try {
						// Insert banner item
						$stmt = $conn->prepare("INSERT INTO banner (name,  image_path, caption_heading, caption_text, link) VALUES (:name, :image_path, :caption_heading, :caption_text, :link)");
						$stmt->execute([
							'name' => $name,
							'image_path' => $image_path,
							'caption_heading' => $caption_heading,
							'caption_text' => $caption_text,
							'link' => $link
						]);
						$_SESSION['success'] = 'banner item added successfully';
					} catch (PDOException $e) {
						$_SESSION['error'] = $e->getMessage();
					}

					$pdo->close();
				} else {
					$_SESSION['error'] = 'Failed to upload image';
				}
			} else {
				$_SESSION['error'] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
			}
		} else {
			$_SESSION['error'] = 'File is not an image.';
		}
	} else {
		$_SESSION['error'] = 'Please upload an image for the banner';
	}
} else {
	$_SESSION['error'] = 'Fill up banner form first';
}

header('location: banner.php');
