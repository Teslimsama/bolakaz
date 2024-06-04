<?php
include 'session.php';

if (isset($_POST['edit'])) {
	$id = $_POST['id'];
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

		// Handle file upload if a new file is provided
		$image_path = '';
		if (!empty($_FILES['image_path']['name'])) {
			$target_dir = "../images/";
			$target_file = $target_dir . basename($_FILES["image_path"]["name"]);
			$uploadOk = 1;
			$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

			$check = getimagesize($_FILES["image_path"]["tmp_name"]);
			if ($check !== false) {
				$uploadOk = 1;
			} else {
				$_SESSION['error'] = "File is not an image.";
				$uploadOk = 0;
			}

			if (file_exists($target_file)) {
				$_SESSION['error'] = "Sorry, file already exists.";
				$uploadOk = 0;
			}

			if ($_FILES["image_path"]["size"] > 5000000) {
				$_SESSION['error'] = "Sorry, your file is too large.";
				$uploadOk = 0;
			}

			if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
				$_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
				$uploadOk = 0;
			}

			if ($uploadOk == 0) {
				$_SESSION['error'] = "Sorry, your file was not uploaded.";
			} else {
				if (move_uploaded_file($_FILES["image_path"]["tmp_name"], $target_file)) {
					$image_path = htmlspecialchars(basename($_FILES["image_path"]["name"]));
				} else {
					$_SESSION['error'] = "Sorry, there was an error uploading your file.";
				}
			}
		}

		try {
			$sql = "UPDATE ads SET text_align=:text_align, discount=:discount, collection=:collection, link=:link";
			if ($image_path) {
				$sql .= ", image_path=:image_path";
			}
			$sql .= " WHERE id=:id";
			$stmt = $conn->prepare($sql);

			$params = [
				'text_align' => $text_align,
				'discount' => $discount,
				'collection' => $collection,
				'link' => $link,
				'id' => $id
			];

			if ($image_path) {
				$params['image_path'] = $image_path;
			}

			$stmt->execute($params);
			$_SESSION['success'] = 'Ad updated successfully';
		} catch (PDOException $e) {
			$_SESSION['error'] = $e->getMessage();
		}
	} else {
		$_SESSION['error'] = 'Invalid category selected';
	}

	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up edit ad form first';
}
header('location: ads.php');
