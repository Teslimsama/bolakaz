<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/sync.php';

	if(isset($_POST['edit'])){
		$id = (int)($_POST['id'] ?? 0);
		$firstname = trim((string)($_POST['firstname'] ?? ''));
		$lastname = trim((string)($_POST['lastname'] ?? ''));
		$email = trim((string)($_POST['email'] ?? ''));
		$password = (string)($_POST['password'] ?? '');
		$address = trim((string)($_POST['address'] ?? ''));
		$contact = trim((string)($_POST['contact'] ?? ''));

		if($id <= 0 || $firstname === '' || $lastname === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)){
			$_SESSION['error'] = 'Invalid user details provided';
			header('location: users.php');
			exit;
		}

		$conn = $pdo->open();
		$stmt = $conn->prepare("SELECT * FROM users WHERE id=:id");
		$stmt->execute(['id'=>$id]);
		$row = $stmt->fetch();
		if(!$row){
			$_SESSION['error'] = 'User not found';
			$pdo->close();
			header('location: users.php');
			exit;
		}

		$hashedPassword = (string)$row['password'];
		if(trim($password) !== ''){
			$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
		}

		try{
			$conn->beginTransaction();
			$stmt = $conn->prepare("UPDATE users SET email=:email, password=:password, firstname=:firstname, lastname=:lastname, address=:address, phone=:contact WHERE id=:id");
			$stmt->execute(['email'=>$email, 'password'=>$hashedPassword, 'firstname'=>$firstname, 'lastname'=>$lastname, 'address'=>$address, 'contact'=>$contact, 'id'=>$id]);
			sync_enqueue_or_fail($conn, 'users', $id);
			$conn->commit();
			$_SESSION['success'] = 'User updated successfully';

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
		$_SESSION['error'] = 'Fill up edit user form first';
	}

	header('location: users.php');
