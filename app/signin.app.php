<?php
include '../session.php';
require_once __DIR__ . '/../lib/customer_accounts.php';
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

		$stmt = $conn->prepare("SELECT id, email, password, status, type, account_state, is_placeholder_email FROM users WHERE email = :email LIMIT 1");
		$stmt->execute(['email' => $email]);
		$row = $stmt->fetch();

		if ($row && password_verify($password, (string)$row['password'])) {
			if (app_customer_can_login($conn, is_array($row) ? $row : [])) {
				session_regenerate_id(true);
				if (app_user_can_access_admin(is_array($row) ? $row : [])) {
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
				$state = app_customer_row_state($conn, is_array($row) ? $row : []);
				if ($state === 'pending_activation') {
					$_SESSION['pending_activation_email'] = (string)$row['email'];
					$_SESSION['error'] = 'Your account is not activated yet. Check your email or resend activation below.';
				} elseif ($state === 'incomplete') {
					unset($_SESSION['pending_activation_email']);
					$_SESSION['error'] = 'This customer profile is not login-enabled yet. Ask an admin to add a real email and enable login.';
				} else {
					unset($_SESSION['pending_activation_email']);
					$_SESSION['error'] = 'Your account cannot sign in right now.';
				}
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
