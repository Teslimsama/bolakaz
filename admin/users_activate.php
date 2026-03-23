<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/sync.php';

	if(isset($_POST['activate'])){
		$id = (int)($_POST['id'] ?? 0);
		
		$conn = $pdo->open();

		try{
			$conn->beginTransaction();
			$stmt = $conn->prepare("UPDATE users SET status=:status WHERE id=:id");
			$stmt->execute(['status'=>1, 'id'=>$id]);
			sync_enqueue_or_fail($conn, 'users', $id);
			$conn->commit();
			$_SESSION['success'] = 'User activated successfully';
		}
		catch(Throwable $e){
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();

	}
	else{
		$_SESSION['error'] = 'Select user to activate first';
	}

	header('location: users.php');
