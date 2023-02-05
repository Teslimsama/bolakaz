<?php

include 'session.php';

if (isset($_POST['submit'])) {
	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$email = $_POST['email'];
	$gender = $_POST['gender'];
	$password = $_POST['password'];
	$phone =$_POST['phone'];
	$repassword = $_POST['repassword'];
	$referral = $_POST['referral'];
	$dob=$_POST['dob'];

	$_SESSION['firstname'] = $firstname;
	$_SESSION['lastname'] = $lastname;
	$_SESSION['email'] = $email;

	// if (!isset($_SESSION['captcha'])) {
	// 	$secret = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe";
	// 	$response = $_POST['g-recaptcha-response'];
	// 	$remoteip = $_SERVER['REMOTE_ADDR'];
	// 	$url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip";
	// 	$data = file_get_contents($url);
	// 	$row = json_decode($data, true);


	// 	if ($row['success'] == true) {
	// 		$_SESSION['captcha'] = time() + (10 * 60);
	// 	} else {
	// 		$_SESSION['error'] = 'Please answer recaptcha correctly';
	// 		header('location: signup');
	// 		exit();
	// 	}
	// }

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

			//generate code
			$set = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$code = substr(str_shuffle($set), 0, 12);

			try {
				$stmt = $conn->prepare("INSERT INTO users (email, password, firstname, lastname, gender,dob, phone, activate_code, created_on, referral) VALUES (:email, :password, :firstname, :lastname, :gender, :dob, :phone, :code, :now, :referral)");
				$stmt->execute(['email' => $email, 'password' => $password, 'firstname' => $firstname, 'lastname' => $lastname,'gender'=>$gender,'dob'=>$dob, 'phone'=>$phone, 'code' => $code, 'now' => $now,'referral'=>$referral]);
				$userid = $conn->lastInsertId();

				$message = "
						<h2>Thank you for Registering.</h2>
						<p>Your Account:</p>
						<p>Email: " . $email . "</p>
						<p>Password: " . $_POST['password'] . "</p>
						<p>Please click the link below to activate your account.</p>
						<a href='http://localhost/bolakaz/activate.php?code=" . $code . "&user=" . $userid . "'>Activate Account</a>
					";




				try {
					$to = $email; // Change this email to your //
					$subject = "$to";
					$header  = 'MIME-Version: 1.0' . "\r\n";
					$header .= 'Content-Type: text/html; charset=ISO-8859-1' . "\r\n";
					$From = "bolajiteslim05@gmail.com";
					mail($to, $subject, $message, $header);
					unset($_SESSION['firstname']);
					unset($_SESSION['lastname']);
					unset($_SESSION['email']);

					$_SESSION['success'] = 'Account created. Check your email to activate.';
					header('location: signup');
				} catch (Exception $e) {
					$_SESSION['error'] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
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
