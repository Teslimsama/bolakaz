<?php

include 'session.php';

if (isset($_POST['reset'])) {
  $email = $_POST['email'];

  $conn = $pdo->open();

  $stmt = $conn->prepare("SELECT *, COUNT(*) AS numrows FROM user WHERE email=:email");
  $stmt->execute(['email' => $email]);
  $row = $stmt->fetch();

  if ($row['numrows'] > 0) {
    //generate code
    $set = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = substr(str_shuffle($set), 0, 15);
    try {
      $stmt = $conn->prepare("UPDATE user SET reset_code=:code WHERE id=:id");
      $stmt->execute(['code' => $code, 'id' => $row['id']]);

      // email message
      $to = $email;
      $subject = 'Password Reset Link';
      $message = "Dear ".$row['firstname'].",<br><br>Please click the link below to reset your password:<br><br>";
      $message .= "<a href='http://localhost/bolakaz/password_reset.php?email=".$email."&code=".$code."'>Reset Password</a><br><br>";
      $message .= "Thank you.<br>";
      $message .= "Bolakaz Team<br>";

      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-Type: text/html; charset=ISO-8859-1' . "\r\n";
      $headers .= 'From: Bolakaz <noreply@unibooks.com.ng>' . "\r\n";

      // send email
      if (mail($to, $subject, $message, $headers)) {
        $_SESSION['success'] = 'Password reset link sent';
      } else {
        $_SESSION['error'] = 'Message could not be sent. Please try again later.';
      }
    } catch (PDOException $e) {
      $_SESSION['error'] = $e->getMessage();
    }
  } else {
    $_SESSION['error'] = 'Email not found';
  }

  $pdo->close();
} else {
  $_SESSION['error'] = 'Input email associated with account';
}

header('location: forgotten_password.php');
