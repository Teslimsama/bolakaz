<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/image_tools.php';
	require_once __DIR__ . '/../lib/sync.php';

	app_admin_require_roles(['admin']);

	if(isset($_POST['upload'])){
		$id = (int)($_POST['id'] ?? 0);
		$uploadError = '';
		$newPhoto = app_store_uploaded_image($_FILES['photo'] ?? [], [
			'required' => true,
			'field_label' => 'User photo',
			'upload_dir' => __DIR__ . '/../images',
			'filename_prefix' => 'user_',
		], $uploadError);
		if ($newPhoto === null) {
			$_SESSION['error'] = $uploadError;
			header('location: users.php');
			exit;
		}
		
		$conn = $pdo->open();
		$oldPhotoPathToDelete = '';

		try{
			$stmt = $conn->prepare("SELECT photo FROM users WHERE id=:id LIMIT 1");
			$stmt->execute(['id' => $id]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$row) {
				$_SESSION['error'] = 'User not found';
				@unlink(__DIR__ . '/../images/' . $newPhoto);
				$pdo->close();
				header('location: users.php');
				exit;
			}

			$conn->beginTransaction();
			$stmt = $conn->prepare("UPDATE users SET photo=:photo WHERE id=:id");
			$stmt->execute(['photo'=>$newPhoto, 'id'=>$id]);
			sync_enqueue_or_fail($conn, 'users', $id);
			$conn->commit();
			$oldPhotoPathToDelete = __DIR__ . '/../images/' . ltrim((string)($row['photo'] ?? ''), '/');
			$_SESSION['success'] = 'User photo updated successfully';
		}
		catch(Throwable $e){
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			@unlink(__DIR__ . '/../images/' . $newPhoto);
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();
		if (
			$oldPhotoPathToDelete !== '' &&
			is_file($oldPhotoPathToDelete) &&
			basename($oldPhotoPathToDelete) !== basename($newPhoto) &&
			strtolower((string) basename($oldPhotoPathToDelete)) !== 'profile.jpg'
		) {
			@unlink($oldPhotoPathToDelete);
		}

	}
	else{
		$_SESSION['error'] = 'Select user to update photo first';
	}

	header('location: users.php');
