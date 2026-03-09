<?php
include '../session.php';
$conn = $pdo->open();

if (isset($_POST['login'])) {

	$email = trim((string)($_POST['email'] ?? ''));
	$password = (string)($_POST['password'] ?? '');

	if ($email === '' || $password === '') {
		$_SESSION['error'] = 'Enter your email and password.';
		$pdo->close();
		header('location: ../signin');
		exit();
	}

	try {

		$stmt = $conn->prepare("SELECT id, email, password, status, type FROM users WHERE email = :email LIMIT 1");
		$stmt->execute(['email' => $email]);
		$row = $stmt->fetch();

		if ($row && password_verify($password, (string)$row['password'])) {
			if ((int)$row['status'] === 1) {
				if (!empty($row['type'])) {
					$_SESSION['admin'] = $row['id'];
				} else {
					$_SESSION['user'] = $row['id'];
				}
				unset($_SESSION['pending_activation_email']);
			} else {
				$_SESSION['pending_activation_email'] = (string)$row['email'];
				$_SESSION['error'] = 'Your account is not activated yet. Check your email or resend activation below.';
			}
		} else {
			$_SESSION['error'] = 'Invalid email or password.';
		}
	} catch (PDOException $e) {
		$_SESSION['error'] = 'Unable to sign in right now. Please try again later.';
	}
} else {
	$_SESSION['error'] = 'Input login credentials first.';
}

$pdo->close();

header('location: ../signin');
exit();
