<?php
include '../session.php';
require_once __DIR__ . '/../lib/mailer.php';

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

if (!isset($_SESSION['captcha'])) {
	$secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? getenv('RECAPTCHA_SECRET_KEY') ?? '';
	$response = $_POST['g-recaptcha-response'] ?? '';
	if ($secret !== '' && $response !== '') {
		$remoteip = $_SERVER['REMOTE_ADDR'] ?? '';
		$url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip";
		$data = file_get_contents($url);
		$row = json_decode((string)$data, true);
		if (!empty($row['success'])) {
			$_SESSION['captcha'] = time() + (10 * 60);
		} else {
			$_SESSION['error'] = 'Please answer recaptcha correctly';
			header('location: ../signup');
			exit();
		}
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

	$insert = $conn->prepare("INSERT INTO users (email, password, type, firstname, lastname, address, phone, gender, dob, photo, status, activate_code, created_on, referral) VALUES (:email, :password, :type, :firstname, :lastname, :address, :phone, :gender, :dob, :photo, :status, :code, :now, :referral)");
	$insert->execute([
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
		'code' => $code,
		'now' => $now,
		'referral' => $refer
	]);
	$userid = $conn->lastInsertId();

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
	error_log('Legacy signup failed: ' . $e->getMessage());
	$_SESSION['error'] = 'Unable to create account right now. Please try again.';
} finally {
	$pdo->close();
}

header('location: ../signup');
exit();
