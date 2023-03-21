<?php
	include 'session.php';

	if(isset($_POST['activate'])){
		$id = $_POST['id'];
		
		$conn = $pdo->open();

		try{
			$stmt = $conn->prepare("UPDATE sales SET Status=:Status WHERE id=:id");
			$stmt->execute(['Status'=>'success', 'id'=>$id]);
			$_SESSION['success'] = 'Status Changed successfully';
		}
		catch(PDOException $e){
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();

	}
	else{
		$_SESSION['error'] = 'Select Sale to Change first';
	}

	header('location: sales');
