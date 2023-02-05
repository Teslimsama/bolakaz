<?php
include 'session.php';
$status=$_GET['status'];

if($status ==='sent'){
	$txid = '';
	$tx_ref ='BolaKaz'. rand(99999, 10000000).'BTRF';
	$Tstatus='pending';
	date_default_timezone_set('Africa/lagos');
	$date = date("Y-m-d");

	$conn = $pdo->open();

	try{

		$stmt = $conn->prepare("INSERT INTO sales (user_id, tx_ref, txid, status, sales_date) VALUES (:user_id, :tx_ref, :txid, :status,:sales_date)");
		$stmt->execute(['user_id'=>$user['id'], 'tx_ref'=>$tx_ref, 'txid'=>$txid, 'status'=>$Tstatus, 'sales_date'=>$date]);
		$salesid = $conn->lastInsertId();

		try{
			$stmt = $conn->prepare("SELECT * FROM cart LEFT JOIN products ON products.id=cart.product_id WHERE user_id=:user_id");
			$stmt->execute(['user_id'=>$user['id']]);

			foreach($stmt as $row){
				$stmt = $conn->prepare("INSERT INTO details (sales_id, product_id, quantity) VALUES (:sales_id, :product_id, :quantity)");
				$stmt->execute(['sales_id'=>$salesid, 'product_id'=>$row['product_id'], 'quantity'=>$row['quantity']]);

			$subtraction_value = $row['quantity'];
			$current_value = $row['qty'];
			$new_value = $current_value - $subtraction_value;
			$id = $row['product_id'];
			$sql = "UPDATE products SET qty = '$new_value' WHERE id = $id";
			$sqll = $conn->prepare($sql);
			$sqll->execute(['qty' => $new_value]);
			}

			$stmt = $conn->prepare("DELETE FROM cart WHERE user_id=:user_id");
			$stmt->execute(['user_id'=>$user['id']]);

			$_SESSION['success'] = 'Transaction successful. Thank you.';

		}
		catch(PDOException $e){
			$_SESSION['error'] = $e->getMessage();
		}

	}
	catch(PDOException $e){
		$_SESSION['error'] = $e->getMessage();
	}

	$pdo->close();
	header('location: profile#trans');
}else{
	header('location:checkout');
}
	
