<?php

include 'session.php';
require_once __DIR__ . '/lib/mailer.php';

if (isset($_POST['submit'])) {
	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$email = $_POST['email'];
	$gender = $_POST['gender'];
	$phone =$_POST['phone'];
	$password = $_POST['password'];
	$repassword = $_POST['repassword'];
	$referral = $_POST['referral'];
	$dob=$_POST['dob'];

	$_SESSION['firstname'] = $firstname;
	$_SESSION['lastname'] = $lastname;
	$_SESSION['email'] = $email;

	if (!isset($_SESSION['captcha'])) {
		$secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? getenv('RECAPTCHA_SECRET_KEY') ?? '';
		$response = $_POST['g-recaptcha-response'] ?? '';
		if ($secret !== '' && $response !== '') {
			$remoteip = $_SERVER['REMOTE_ADDR'];
			$url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip";
			$data = file_get_contents($url);
			$row = json_decode($data, true);

			if (!empty($row['success']) && $row['success'] == true) {
				$_SESSION['captcha'] = time() + (10 * 60);
			} else {
				$_SESSION['error'] = 'Please answer recaptcha correctly';
				header('location: signup');
				exit();
			}
		}
	}

	if ($password != $repassword) {
		$_SESSION['error'] = 'Passwords did not match';
		header('location: signup');
	} else {
		$conn = $pdo->open();

		$stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM users WHERE email=:email");
		$stmt->execute(['email' => $email]);
		$row = $stmt->fetch();
		if ($row['numrows'] > 0) {
			$_SESSION['error'] = 'Email already taken';
			header('location: signup');
		} else {
			$now = date('Y-m-d');
			$password = password_hash($password, PASSWORD_DEFAULT);

			// Generate activation token.
			$code = bin2hex(random_bytes(16));

			try {
				$stmt = $conn->prepare("INSERT INTO users (email, password, firstname, lastname, gender,dob, phone, activate_code, created_on, referral) VALUES (:email, :password, :firstname, :lastname, :gender, :dob, :phone, :code, :now, :referral)");
				$stmt->execute(['email' => $email, 'password' => $password, 'firstname' => $firstname, 'lastname' => $lastname,'gender'=>$gender,'dob'=>$dob, 'phone'=>$phone, 'code' => $code, 'now' => $now,'referral'=>$referral]);
				$userid = $conn->lastInsertId();

				




				try {
					$activateUrl = app_base_url() . '/activate.php?code=' . urlencode($code) . '&user=' . urlencode((string)$userid);
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
						unset($_SESSION['firstname']);
						unset($_SESSION['lastname']);
						unset($_SESSION['email']);
						$_SESSION['success'] = 'Account created. Check your email to activate.';
					} else {
						$_SESSION['error'] = 'Account created, but we could not send activation email now. Please try again.';
					}
					header('location: signup');
				} catch (Throwable $e) {
					$_SESSION['error'] = 'Message could not be sent at this time.';
					header('location: signup');
				}
			} catch (PDOException $e) {
				$_SESSION['error'] = $e->getMessage();
				header('location: register');
			}

			$pdo->close();
		}
	}
} else {
	$_SESSION['error'] = 'Fill up signup form first';
	header('location: signup');
}
