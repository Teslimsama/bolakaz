<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/sync.php';
	require_once __DIR__ . '/../lib/customer_accounts.php';

	if(isset($_POST['add'])){
		$fullName = trim((string)($_POST['full_name'] ?? ''));
		$email = trim((string)($_POST['email'] ?? ''));
		$address = trim((string)($_POST['address'] ?? ''));
		$contact = trim((string)($_POST['contact'] ?? ''));

		if($fullName === ''){
			$_SESSION['error'] = 'Please provide a customer name';
			header('location: users.php');
			exit;
		}

		$conn = $pdo->open();

		$filename = '';
		if(!empty($_FILES['photo']['name']) && is_uploaded_file($_FILES['photo']['tmp_name'])){
			$ext = pathinfo((string)$_FILES['photo']['name'], PATHINFO_EXTENSION);
			$safeExt = preg_replace('/[^a-zA-Z0-9]/', '', (string)$ext);
			$filename = uniqid('user_', true) . ($safeExt !== '' ? '.' . strtolower($safeExt) : '');
			move_uploaded_file($_FILES['photo']['tmp_name'], '../images/'.$filename);	
		}
		try{
			$conn->beginTransaction();
			$customer = app_customer_create_incomplete_profile($conn, [
				'full_name' => $fullName,
				'email' => $email,
				'address' => $address,
				'phone' => $contact,
				'photo' => $filename,
			]);
			sync_enqueue_or_fail($conn, 'users', (int) $customer['id']);
			$conn->commit();
			$_SESSION['success'] = 'Customer saved successfully. Add a real email later and use "Enable Login / Regenerate Temp Password" when they need access.';

		}
		catch(Throwable $e){
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			if ($filename !== '') {
				@unlink(__DIR__ . '/../images/' . $filename);
			}
			$_SESSION['error'] = $e->getMessage();
		}

		$pdo->close();
	}
	else{
		$_SESSION['error'] = 'Fill up user form first';
	}

	header('location: users.php');
