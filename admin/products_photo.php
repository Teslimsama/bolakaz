<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/catalog_v2.php';
	require_once __DIR__ . '/../lib/image_tools.php';

	if(isset($_POST['upload'])){
		$id = (int)($_POST['id'] ?? 0);
		$filename = (string)($_FILES['photo']['name'] ?? '');

		$conn = $pdo->open();

		$stmt = $conn->prepare("SELECT * FROM products WHERE id=:id");
		$stmt->execute(['id'=>$id]);
		$row = $stmt->fetch();
		if (!$row) {
			$_SESSION['error'] = 'Product not found.';
			$pdo->close();
			header('location: products');
			exit();
		}

		$new_filename = (string)($row['photo'] ?? '');
		$allowTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
		$allowMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
		$photoOptimizationWarning = '';

		if(!empty($filename)){
			$ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
			if (!in_array($ext, $allowTypes, true)) {
				$_SESSION['error'] = 'Invalid photo format.';
				$pdo->close();
				header('location: products');
				exit();
			}
			$new_filename = $row['slug'].'_'.time().'.'.$ext;
			$targetFile = '../images/'.$new_filename;
			if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
				$_SESSION['error'] = 'Unable to upload photo.';
				$pdo->close();
				header('location: products');
				exit();
			}
			$optimizationError = '';
			if (!app_optimize_image($targetFile, 1200, 80, $optimizationError)) {
				if (!app_uploaded_image_is_valid($targetFile, $allowMime)) {
					@unlink($targetFile);
					$_SESSION['error'] = 'Photo processing failed.';
					$pdo->close();
					header('location: products');
					exit();
				}

				$photoOptimizationWarning = ' Photo was uploaded without optimization due to server image-processing limits.';
				if (function_exists('app_log')) {
					app_log('warning', 'Product photo optimization skipped; keeping original upload.', [
						'product_id' => $id,
						'file' => $filename,
						'error' => $optimizationError !== '' ? $optimizationError : 'Native image optimizer could not process the upload.',
					]);
				}
			}
		}
		
		try{
			$stmt = $conn->prepare("UPDATE products SET photo=:photo WHERE id=:id");
			$stmt->execute(['photo'=>$new_filename, 'id'=>$id]);

			if (catalog_v2_table_exists($conn, 'products_v2')) {
				$syncStmt = $conn->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
				$syncStmt->execute(['id' => $id]);
				$row = $syncStmt->fetch(PDO::FETCH_ASSOC);
				if ($row) {
					catalog_v2_sync_product_from_legacy($conn, $row);
				}
			}
			$_SESSION['success'] = 'Product photo updated successfully.' . $photoOptimizationWarning;
		}
		catch(PDOException $e){
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();

	}
	else{
		$_SESSION['error'] = 'Select product to update photo first';
	}

	header('location: products');
