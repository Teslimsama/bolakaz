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

	if (!app_user_can_access_admin(is_array($admin) ? $admin : [])) {
		unset($_SESSION['admin']);
		$_SESSION['error'] = 'Your account does not have admin access.';
		header('location: ../signin');
		exit();
	}

	function app_admin_has_role(array $allowedRoles): bool
	{
		global $admin;

		$normalizedAllowedRoles = array_map('strtolower', $allowedRoles);
		$currentRole = app_normalize_user_role($admin['type'] ?? null);
		return in_array($currentRole, $normalizedAllowedRoles, true);
	}

	function app_admin_require_roles(array $allowedRoles): void
	{
		if (app_admin_has_role($allowedRoles)) {
			return;
		}

		if (!headers_sent()) {
			http_response_code(403);
		}

		$_SESSION['error'] = 'You do not have permission to perform that action.';

		$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
		if ($isAjax) {
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode([
				'error' => true,
				'message' => 'Forbidden',
			]);
			exit();
		}

		header('location: home.php');
		exit();
	}
