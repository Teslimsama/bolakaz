<?php
	include ('CreateDb.php');
	include_once 'security.php';
	app_start_session();
	app_get_csrf_token();
	app_require_csrf_for_mutations();

	$user = [];


	if(isset($_SESSION['admin'])){
		header('location: admin/home.php');
		exit();
	}

	if(isset($_SESSION['user'])){
		$conn = $pdo->open();

		try{
			$stmt = $conn->prepare("SELECT * FROM users WHERE id=:id");
			$stmt->execute(['id'=>$_SESSION['user']]);
			$user = $stmt->fetch() ?: [];
		}
		catch(PDOException $e){
			$user = [];
			if (function_exists('app_log')) {
				app_log('error', 'Unable to load storefront user session', [
					'user_id' => $_SESSION['user'] ?? null,
					'error' => $e->getMessage(),
				]);
			}
		}

		$pdo->close();

		if (empty($user['id'])) {
			unset($_SESSION['user']);
		}
	}
?>
