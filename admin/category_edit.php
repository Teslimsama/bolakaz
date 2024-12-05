<?php
include 'session.php';
include 'slugify.php';

if (isset($_POST['edit'])) {
	$id = $_POST['id'];
	$name = htmlspecialchars($_POST['name']); // Sanitize input data
	$slug = slugify($_POST['name']); // Generate slug
	$is_parent = isset($_POST['is_parent']) ? 1 : 0; // Checkbox for is_parent
	$parent_id = $is_parent ? null : htmlspecialchars($_POST['parent_id']); // Parent ID only if not a parent category
	$status = htmlspecialchars($_POST['status']); // Category status (active/inactive)

	// Check if a file was uploaded
	if (isset($_FILES['cat-image']) && $_FILES['cat-image']['error'] === UPLOAD_ERR_OK) {
		$target_dir = "../images/"; // Directory where the image will be uploaded
		$target_file = $target_dir . basename($_FILES["cat-image"]["name"]);
		$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

		// Check if the file is a valid image
		$check = getimagesize($_FILES["cat-image"]["tmp_name"]);
		if ($check !== false) {
			// Allow certain file formats
			if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
				// Move uploaded file to destination directory
				if (move_uploaded_file($_FILES["cat-image"]["tmp_name"], $target_file)) {
					$cat_image = $target_file;

					// Update category with new image path
					try {
						$stmt = $conn->prepare("UPDATE category 
                                                SET name=:name, cat_slug=:cat_slug, cat_image=:cat_image, 
                                                    is_parent=:is_parent, parent_id=:parent_id, status=:status 
                                                WHERE id=:id");
						$stmt->execute([
							'name' => $name,
							'cat_slug' => $slug,
							'cat_image' => $cat_image,
							'is_parent' => $is_parent,
							'parent_id' => $parent_id,
							'status' => $status,
							'id' => $id
						]);
						$_SESSION['success'] = 'Category updated successfully';
					} catch (PDOException $e) {
						$_SESSION['error'] = $e->getMessage();
					}
				} else {
					$_SESSION['error'] = 'Error uploading image.';
				}
			} else {
				$_SESSION['error'] = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
			}
		} else {
			$_SESSION['error'] = 'File is not an image.';
		}
	} else {
		// Update category without changing the image
		try {
			$stmt = $conn->prepare("UPDATE category 
                                    SET name=:name, cat_slug=:cat_slug, 
                                        is_parent=:is_parent, parent_id=:parent_id, status=:status 
                                    WHERE id=:id");
			$stmt->execute([
				'name' => $name,
				'cat_slug' => $slug,
				'is_parent' => $is_parent,
				'parent_id' => $parent_id,
				'status' => $status,
				'id' => $id
			]);
			$_SESSION['success'] = 'Category updated successfully';
		} catch (PDOException $e) {
			$_SESSION['error'] = $e->getMessage();
		}
	}
	$pdo->close();
} else {
	$_SESSION['error'] = 'Fill up edit category form first';
}

header('location: category.php');
