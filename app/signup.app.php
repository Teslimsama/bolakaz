<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'session.php';

if (isset($_POST['signup'])) {
	$firstname = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$email = $_POST['email'];
	$phone = $_POST['phone'];
	$state = $_POST['state'];
	$user = $_POST['username'];
	$refer = $_POST['referral'];
	$gender = $_POST['gender'];
	$password = $_POST['password'];
	$repassword = $_POST['repassword'];

	$_SESSION['firstname'] = $firstname;
	$_SESSION['lastname'] = $lastname;
	$_SESSION['email'] = $email;

	if (!isset($_SESSION['captcha'])) {
		$secret = "6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe";
		$response = $_POST['g-recaptcha-response'];
		$remoteip = $_SERVER['REMOTE_ADDR'];
		$url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$response&remoteip=$remoteip";
		$data = file_get_contents($url);
		$row = json_decode($data, true);
		if ($row['success'] == "false") {
			$_SESSION['captcha'] = time() + (10 * 60);
		} else {
			$_SESSION['error'] = 'Please answer recaptcha correctly';
			header('location: signup.php');
			exit();
		}
	}

	if ($password != $repassword) {
		$_SESSION['error'] = 'Passwords did not match';
		header('location: ../signup.php');
	} else {
		$conn = $pdo->open();

		$stmt = $conn->prepare("SELECT COUNT(*) AS numrows FROM users WHERE email=:email");
		$stmt->execute(['email' => $email]);
		$row = $stmt->fetch();
		if ($row['numrows'] > 0) {
			$_SESSION['error'] = 'Email already taken';
			header('location: ../signup.php');
		} else {
			$now = date('Y-m-d');
			$password = password_hash($password, PASSWORD_DEFAULT);

			//generate code
			$set = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$code = substr(str_shuffle($set), 0, 12);

			try {
				$stmt = $conn->prepare("INSERT INTO users (email, password, firstname, lastname, phone, state, refer, username, gender, activate_code, created_on) VALUES (:email, :password, :firstname, :lastname, :phone, :state, :refer, :username, :gender, :code, :now)");
				$stmt->execute(['email' => $email, 'password' => $password, 'firstname' => $firstname, 'lastname' => $lastname, 'phone' => $phone, 'state' => $state, 'refer' => $refer, 'username' => $user, 'gender' => $gender, 'code' => $code, 'now' => $now]);
				$userid = $conn->lastInsertId();

				$message = "
						<h2>Thank you for Registering.</h2>
						<p>Your Account:</p>
						<p>Email: " . $email . "</p>
						<p>Password: " . $_POST['password'] . "</p>
						<p>Please click the link below to activate your account.</p>
						<a href='http://localhost/bolakaz/activate.php?code=" . $code . "&user=" . $userid . "'>Activate Account</a>
					";

				//Load phpmailer
				require 'vendor/autoload.php';

				$mail = new PHPMailer(true);
				try {
					//Server settings
					$mail->isSMTP();
					$mail->Host = 'smtp.gmail.com';
					$mail->SMTPAuth = true;
					$mail->Username = 'bolajiteslim05@gmail.com';
					$mail->Password = 'bioombteyxpcqxzb';
					$mail->SMTPOptions = array(
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
							'allow_self_signed' => true
						)
					);
					$mail->SMTPSecure = 'ssl';
					$mail->Port = 465;

					$mail->setFrom('bolajiteslim05@gmail.com');

					//Recipients
					$mail->addAddress($email);
					$mail->addReplyTo('bolajiteslim05@gmail.com');

					//Content
					$mail->isHTML(true);
					$mail->Subject = 'Bolakaz Sign Up';
					$mail->Body    = $message;

					$mail->send();

					unset($_SESSION['firstname']);
					unset($_SESSION['lastname']);
					unset($_SESSION['email']);

					$_SESSION['success'] = 'Account created. Check your email to activate.';
					header('location: ../signup.php');
				} catch (Exception $e) {
					$_SESSION['error'] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
					header('location: ../signup.php');
				}
			} catch (PDOException $e) {
				$_SESSION['error'] = $e->getMessage();
				header('location: signup.app.php');
			}

			$pdo->close();
		}
	}
} else {
	$_SESSION['error'] = 'Fill up signup form first';
	header('location: ../signup.php');
}
