<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/catalog_v2.php';

	if(isset($_POST['id']) && (int)$_POST['id'] > 0){
		$id = (int)$_POST['id'];
		
		$conn = $pdo->open();

		try{
			$stmt = $conn->prepare("UPDATE products SET product_status = 0 WHERE id=:id");
			$stmt->execute(['id'=>$id]);

			if (catalog_v2_table_exists($conn, 'products_v2')) {
				$syncStmt = $conn->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
				$syncStmt->execute(['id' => $id]);
				$row = $syncStmt->fetch(PDO::FETCH_ASSOC);
				if ($row) {
					catalog_v2_sync_product_from_legacy($conn, $row);
				}
			}

			$_SESSION['success'] = 'Product archived successfully';
		}
		catch(PDOException $e){
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();
	}
	else{
		$_SESSION['error'] = 'Select product to archive first';
	}

	header('location: products.php');
