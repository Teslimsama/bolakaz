<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/sync.php';
	require_once __DIR__ . '/../lib/customer_accounts.php';

	app_admin_require_roles(['admin']);

	if(isset($_POST['activate'])){
		$id = (int)($_POST['id'] ?? 0);
		
		$conn = $pdo->open();

		try{
			$conn->beginTransaction();
			$result = app_customer_enable_login($conn, $id);
			sync_enqueue_or_fail($conn, 'users', $id);
			$conn->commit();
			$_SESSION['success'] = 'Login enabled for ' . $result['customer_name'] . '. Temporary password: ' . $result['password'];
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
		$_SESSION['error'] = 'Select a customer first';
	}

	header('location: users.php');
