<?php
	include 'session.php';

	$conn = $pdo->open();

	$output = array('error'=>false);

	$id = (string)($_POST['id'] ?? '');
	$qty = $_POST['qty'];

	if(isset($_SESSION['user'])){
		try{
			$stmt = $conn->prepare("UPDATE cart SET quantity=:quantity WHERE id=:id");
			$stmt->execute(['quantity'=>$qty, 'id'=>$id]);
			$output['message'] = 'Updated';
		}
		catch(PDOException $e){
			$output['message'] = $e->getMessage();
		}
	}
	else{
		if (strpos($id, 's') === 0) {
			$sessionKey = substr($id, 1);
			if (isset($_SESSION['cart'][$sessionKey])) {
				$_SESSION['cart'][$sessionKey]['quantity'] = $qty;
				$output['message'] = 'Updated';
			}
		} else {
			foreach($_SESSION['cart'] as $key => $row){
				if((string)$row['productid'] === $id){
					$_SESSION['cart'][$key]['quantity'] = $qty;
					$output['message'] = 'Updated';
				}
			}
		}
	}

	$pdo->close();
	echo json_encode($output);
