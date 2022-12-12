<?php
	
	include 'includes/session.php';

	if(isset($_POST['reset'])){
		$email = $_POST['email'];

		$conn = $pdo->open();

		$stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM users WHERE email=:email");
		$stmt->execute(['email'=>$email]);
		$row = $stmt->fetch();

		if($row['numrows'] > 0){
			//generate code
			$set='123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$code=substr(str_shuffle($set), 0, 15);
			try{
				$stmt = $conn->prepare("UPDATE users SET reset_code=:code WHERE id=:id");
				$stmt->execute(['code'=>$code, 'id'=>$row['id']]);
			
				$tempfile ="./email.php";
				     
			try {
			

			$to = "bolajiteslim05@gmail.com"; // Change this email to your //
			$subject = "$m_subject:  $to";
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-Type: text/html; charset=ISO-8859-1' . "\r\n";
			$From = "bolajiteslim05@gmail.com";
			// Create email headers
			
			
			$header .= 'From: Bolakaz Enterprise <bolajiteslim05gmail.com>' . "\r\n";
			
			if (file_exists($tempfile)) {
				
				$message = file_get_contents($tempfile);
					$_SESSION['error'] = "sent";
			}else{
				die($_SESSION['error'] = "unable to locate file ");
			}

			mail($email, $subject, $message, $header);
			$_SESSION['success'] = 'Password reset link sent';
			     
			    } 
			    catch (Exception $e) {
			        $_SESSION['error'] = 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo;
			    }
			}
			catch(PDOException $e){
				$_SESSION['error'] = $e->getMessage();
			}
		}
		else{
			$_SESSION['error'] = 'Email not found';
		}

		$pdo->close();

	}
	else{
		$_SESSION['error'] = 'Input email associated with account';
	}

	header('location: password_forgot.php');
