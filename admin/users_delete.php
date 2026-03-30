<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/sync.php';

	app_admin_require_roles(['admin']);

	if(isset($_POST['delete'])){
		$id = (int)($_POST['id'] ?? 0);
		
		$conn = $pdo->open();
		$photoPath = '';

		try{
			$stmt = $conn->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
			$stmt->execute(['id' => $id]);
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$user) {
				$_SESSION['error'] = 'User not found';
				$pdo->close();
				header('location: users.php');
				exit;
			}

			$conn->beginTransaction();
			sync_enqueue_delete_or_fail($conn, 'users', $user);
			$stmt = $conn->prepare("DELETE FROM users WHERE id=:id");
			$stmt->execute(['id'=>$id]);
			$conn->commit();
			$photoPath = __DIR__ . '/../images/' . ltrim((string)($user['photo'] ?? ''), '/');

			$_SESSION['success'] = 'User deleted successfully';
		}
		catch(Throwable $e){
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();
		if ($photoPath !== '' && is_file($photoPath)) {
			@unlink($photoPath);
		}
	}
	else{
		$_SESSION['error'] = 'Select user to delete first';
	}

	header('location: users.php');
