<?php
	include 'session.php';
	require_once __DIR__ . '/../lib/sync.php';

	if(isset($_POST['add'])){
		$firstname = trim((string)($_POST['firstname'] ?? ''));
		$lastname = trim((string)($_POST['lastname'] ?? ''));
		$email = trim((string)($_POST['email'] ?? ''));
		$password = (string)($_POST['password'] ?? '');
		$address = trim((string)($_POST['address'] ?? ''));
		$contact = trim((string)($_POST['contact'] ?? ''));

		if($firstname === '' || $lastname === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === ''){
			$_SESSION['error'] = 'Please provide valid user details';
			header('location: users.php');
			exit;
		}

		$conn = $pdo->open();

		$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM users WHERE email=:email");
		$stmt->execute(['email'=>$email]);
		$row = $stmt->fetch();

		if($row['numrows'] > 0){
			$_SESSION['error'] = 'Email already taken';
		}
		else{
			$password = password_hash($password, PASSWORD_DEFAULT);
			$filename = '';
			$now = date('Y-m-d');
			if(!empty($_FILES['photo']['name']) && is_uploaded_file($_FILES['photo']['tmp_name'])){
				$ext = pathinfo((string)$_FILES['photo']['name'], PATHINFO_EXTENSION);
				$safeExt = preg_replace('/[^a-zA-Z0-9]/', '', (string)$ext);
				$filename = uniqid('user_', true) . ($safeExt !== '' ? '.' . strtolower($safeExt) : '');
				move_uploaded_file($_FILES['photo']['tmp_name'], '../images/'.$filename);	
			}
			try{
				$conn->beginTransaction();
				$stmt = $conn->prepare("INSERT INTO users (email, password, firstname, lastname, address, phone, photo, status, created_on) VALUES (:email, :password, :firstname, :lastname, :address, :contact, :photo, :status, :created_on)");
				$stmt->execute(['email'=>$email, 'password'=>$password, 'firstname'=>$firstname, 'lastname'=>$lastname, 'address'=>$address, 'contact'=>$contact, 'photo'=>$filename, 'status'=>1, 'created_on'=>$now]);
				$userId = (int) $conn->lastInsertId();
				sync_enqueue_or_fail($conn, 'users', $userId);
				$conn->commit();
				$_SESSION['success'] = 'User added successfully';

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
		}

		$pdo->close();
	}
	else{
		$_SESSION['error'] = 'Fill up user form first';
	}

	header('location: users.php');
