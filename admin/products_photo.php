<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/catalog_v2.php';
	require_once __DIR__ . '/../vendor/autoload.php';
	use Intervention\Image\ImageManagerStatic as Image;

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
			header('location: products.php');
			exit();
		}

		$new_filename = (string)($row['photo'] ?? '');
		$allowTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

		if(!empty($filename)){
			$ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
			if (!in_array($ext, $allowTypes, true)) {
				$_SESSION['error'] = 'Invalid photo format.';
				$pdo->close();
				header('location: products.php');
				exit();
			}
			$new_filename = $row['slug'].'_'.time().'.'.$ext;
			$targetFile = '../images/'.$new_filename;
			if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
				$_SESSION['error'] = 'Unable to upload photo.';
				$pdo->close();
				header('location: products.php');
				exit();
			}
			try {
				$image = Image::make($targetFile);
				$image->orientate();
				$image->resize(1200, null, function ($constraint) {
					$constraint->aspectRatio();
					$constraint->upsize();
				})->save($targetFile, 80);
			} catch (Throwable $e) {
				@unlink($targetFile);
				$_SESSION['error'] = 'Photo processing failed.';
				$pdo->close();
				header('location: products.php');
				exit();
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
			$_SESSION['success'] = 'Product photo updated successfully';
		}
		catch(PDOException $e){
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();

	}
	else{
		$_SESSION['error'] = 'Select product to update photo first';
	}

	header('location: products.php');
