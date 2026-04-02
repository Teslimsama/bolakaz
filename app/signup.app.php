<?php
include '../session.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../lib/customer_accounts.php';
require_once __DIR__ . '/../lib/hcaptcha.php';
require_once __DIR__ . '/../lib/sync.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	if (!isset($_SESSION['error'])) {
		$_SESSION['error'] = 'Fill up signup form first';
	}
	header('location: ../signup');
	exit();
}

$firstname = trim((string)($_POST['firstname'] ?? ''));
$lastname = trim((string)($_POST['lastname'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$state = trim((string)($_POST['state'] ?? ''));
$refer = trim((string)($_POST['referral'] ?? ''));
$gender = trim((string)($_POST['gender'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$repassword = (string)($_POST['repassword'] ?? '');
$captchaBypassedForLocal = app_is_local_env();
$hcaptchaToken = trim((string)($_POST['h-captcha-response'] ?? ''));
$hcaptchaSiteKey = app_hcaptcha_site_key();

if ($firstname === '' || $lastname === '' || $email === '' || $phone === '' || $gender === '' || $password === '' || $repassword === '') {
	$_SESSION['error'] = 'Please complete all signup fields.';
	header('location: ../signup');
	exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$_SESSION['error'] = 'Enter a valid email address.';
	header('location: ../signup');
	exit();
}

$_SESSION['firstname'] = $firstname;
$_SESSION['lastname'] = $lastname;
$_SESSION['email'] = $email;

if (!$captchaBypassedForLocal && $hcaptchaSiteKey !== '') {
	if (!app_hcaptcha_has_server_config()) {
		$_SESSION['error'] = 'Signup security is not configured correctly. Please contact support.';
		header('location: ../signup');
		exit();
	}

	if ($hcaptchaToken === '') {
		$_SESSION['error'] = 'Complete the security check and try again.';
		header('location: ../signup');
		exit();
	}

	$verification = app_hcaptcha_verify($hcaptchaToken);
	if (empty($verification['success'])) {
		$_SESSION['error'] = 'Security verification failed. Please try again.';
		header('location: ../signup');
		exit();
	}
}

if ($password !== $repassword) {
	$_SESSION['error'] = 'Passwords did not match';
	header('location: ../signup');
	exit();
}

$conn = $pdo->open();

try {
	$stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM users WHERE email=:email");
	$stmt->execute(['email' => $email]);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if ((int)($row['numrows'] ?? 0) > 0) {
		$_SESSION['error'] = 'Email already taken';
		header('location: ../signup');
		exit();
	}

	$now = date('Y-m-d');
	$passwordHash = password_hash($password, PASSWORD_DEFAULT);
	$code = bin2hex(random_bytes(16));
	$userUuid = app_customer_generate_uuid();

	$conn->beginTransaction();
	$columns = ['uuid', 'email', 'password', 'type', 'firstname', 'lastname', 'address', 'phone', 'gender', 'dob', 'photo', 'status', 'activate_code', 'created_on', 'referral'];
	$params = [
		'uuid' => $userUuid,
		'email' => $email,
		'password' => $passwordHash,
		'type' => 0,
		'firstname' => $firstname,
		'lastname' => $lastname,
		'address' => $state,
		'phone' => $phone,
		'gender' => $gender,
		'dob' => '',
		'photo' => '',
		'status' => 0,
		'activate_code' => $code,
		'created_on' => $now,
		'referral' => $refer,
	];
	if (app_customer_db_has_column($conn, 'users', 'account_state')) {
		$columns[] = 'account_state';
		$params['account_state'] = 'pending_activation';
	}
	if (app_customer_db_has_column($conn, 'users', 'is_placeholder_email')) {
		$columns[] = 'is_placeholder_email';
		$params['is_placeholder_email'] = 0;
	}
	$placeholders = [];
	foreach ($columns as $column) {
		$placeholders[] = ':' . $column;
	}
	$insert = $conn->prepare("INSERT INTO users (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")");
	$insert->execute($params);
	$userid = (int) $conn->lastInsertId();
	sync_enqueue_or_fail($conn, 'users', $userid);
	$conn->commit();

	$activateUrl = app_base_url() . '/activate?code=' . urlencode($code) . '&user=' . urlencode((string)$userid);
	$subject = 'Activate your Bolakaz account';
	$contentHtml = '
		<p>Welcome to Bolakaz, ' . e($firstname) . '.</p>
		<p>Confirm your email to activate your account and continue shopping.</p>
		<p>If the button does not open, copy this URL into your browser:</p>
		<p><a href="' . e($activateUrl) . '" style="color:#128278;word-break:break-all;">' . e($activateUrl) . '</a></p>
	';
	$htmlBody = app_email_template(
		'Confirm Your Email',
		'Activate your account to complete setup.',
		$contentHtml,
		'Activate Account',
		$activateUrl
	);
	$textBody = "Activate your Bolakaz account\n\nUse this link:\n" . $activateUrl;

	if (app_send_email($email, $subject, $htmlBody, $textBody)) {
		unset($_SESSION['firstname'], $_SESSION['lastname'], $_SESSION['email']);
		$mailMode = strtolower((string)app_mail_env('MAIL_MAILER', 'smtp'));
		if ($mailMode === 'log') {
			$_SESSION['success'] = 'Account created. Activation mail is in log mode. Check storage/logs/app.log for the activation link.';
		} else {
			$_SESSION['success'] = 'Account created. Check your email to activate.';
		}
	} else {
		$_SESSION['error'] = 'Account created, but we could not send activation email now. Please try again.';
	}
} catch (Throwable $e) {
	if ($conn->inTransaction()) {
		$conn->rollBack();
	}
	error_log('Legacy signup failed: ' . $e->getMessage());
	$_SESSION['error'] = 'Unable to create account right now. Please try again.';
} finally {
	$pdo->close();
}

header('location: ../signup');
exit();
