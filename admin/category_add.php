<?php
include 'session.php';
include 'slugify.php';

if (isset($_POST['add'])) {
	$name = htmlspecialchars($_POST['name']); // Sanitize input data
	$slug = slugify($_POST['name']); // Generate slug
	$is_parent = isset($_POST['is_parent']) ? 1 : 0; // Checkbox for is_parent
	$parent_id = $is_parent ? null : htmlspecialchars($_POST['parent_id']); // Parent ID only if not a parent category
	$status = htmlspecialchars($_POST['status']); // Category status (active/inactive)

	// Handle category image upload
	if (isset($_FILES['cat-image']) && $_FILES['cat-image']['error'] === UPLOAD_ERR_OK) {
		$target_dir = "../images/"; // Directory where the image will be uploaded
		$target_file = $target_dir . basename($_FILES["cat-image"]["name"]);
		$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

		// Check if the file is an actual image or fake image
		$check = getimagesize($_FILES["cat-image"]["tmp_name"]);
		if ($check !== false) {
			// Allow certain file formats
			if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
				// Move uploaded file to destination directory
				move_uploaded_file($_FILES["cat-image"]["tmp_name"], $target_file);
				$cat_image = $target_file;

				$conn = $pdo->open();

				// Check if category already exists
				$stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM category WHERE name=:name");
				$stmt->execute(['name' => $name]);
				$row = $stmt->fetch();

				if ($row['numrows'] > 0) {
					$_SESSION['error'] = 'Category already exists';
				} else {
					try {
						// Insert category data into the database
						$stmt = $conn->prepare("INSERT INTO category (name, cat_slug, cat_image, is_parent, parent_id, status) 
                                                VALUES (:name, :cat_slug, :cat_image, :is_parent, :parent_id, :status)");
						$stmt->execute([
							'name' => $name,
							'cat_slug' => $slug,
							'cat_image' => $cat_image,
							'is_parent' => $is_parent,
							'parent_id' => $parent_id,
							'status' => $status,
						]);
						$_SESSION['success'] = 'Category added successfully';
					} catch (PDOException $e) {
						$_SESSION['error'] = $e->getMessage();
					}
				}

				$pdo->close();
			} else {
				$_SESSION['error'] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
			}
		} else {
			$_SESSION['error'] = 'File is not an image.';
		}
	} else {
		$_SESSION['error'] = 'Category image is required.';
	}
} else {
	$_SESSION['error'] = 'Fill up category form first';
}
header('location: category.php');
