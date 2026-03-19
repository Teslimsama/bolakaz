<?php
	include '../CreateDb.php';
	include_once '../security.php';
	app_start_session();
	app_get_csrf_token();
	app_require_csrf_for_mutations();

	if (!headers_sent()) {
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header('Expires: 0');
	}

	if(!isset($_SESSION['admin']) || trim($_SESSION['admin']) == ''){
		header('location: ../index');
		exit();
	}

	$admin = [];
	$conn = $pdo->open();

	$stmt = $conn->prepare("SELECT * FROM users WHERE id=:id");
	$stmt->execute(['id'=>$_SESSION['admin']]);
	$admin = $stmt->fetch() ?: [];

	$pdo->close();

	if (empty($admin['id'])) {
		unset($_SESSION['admin']);
		header('location: ../index');
		exit();
	}
