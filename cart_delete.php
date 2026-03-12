<?php
	include 'session.php';

	$conn = $pdo->open();

	$output = array('error'=>false);
	$id = (string)($_POST['id'] ?? '');

	if(isset($_SESSION['user'])){
		try{
			$stmt = $conn->prepare("DELETE FROM cart WHERE id=:id");
			$stmt->execute(['id'=>$id]);
			$output['message'] = 'Deleted';
			
		}
		catch(PDOException $e){
			$output['message'] = $e->getMessage();
		}
	}
	else{
		if (strpos($id, 's') === 0) {
			$sessionKey = substr($id, 1);
			if (isset($_SESSION['cart'][$sessionKey])) {
				unset($_SESSION['cart'][$sessionKey]);
				$output['message'] = 'Deleted';
			}
		} else {
			foreach($_SESSION['cart'] as $key => $row){
				if((string)$row['productid'] === $id){
					unset($_SESSION['cart'][$key]);
					$output['message'] = 'Deleted';
				}
			}
		}
	}

	$pdo->close();
	echo json_encode($output);
