<?php
include '../session.php';
$conn = $pdo->open();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$redirect = '../signin';

	$email = trim((string)($_POST['email'] ?? ''));
	$password = (string)($_POST['password'] ?? '');

	if ($email === '' || $password === '') {
		$_SESSION['error'] = 'Enter your email and password.';
		$pdo->close();
		session_write_close();
		header('location: ' . $redirect);
		exit();
	}

	try {

		$stmt = $conn->prepare("SELECT id, email, password, status, type FROM users WHERE email = :email LIMIT 1");
		$stmt->execute(['email' => $email]);
		$row = $stmt->fetch();

		if ($row && password_verify($password, (string)$row['password'])) {
			if ((int)$row['status'] === 1) {
				session_regenerate_id(true);
				if (!empty($row['type'])) {
					$_SESSION['admin'] = $row['id'];
					unset($_SESSION['user']);
					$redirect = '../admin/home';
				} else {
					$_SESSION['user'] = $row['id'];
					unset($_SESSION['admin']);
					$redirect = '../cart';
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

	$pdo->close();
	session_write_close();
	header('location: ' . $redirect);
	exit();
} else {
	if (!isset($_SESSION['error'])) {
		$_SESSION['error'] = 'Input login credentials first.';
	}
}

$pdo->close();
session_write_close();
header('location: ../signin');
exit();
