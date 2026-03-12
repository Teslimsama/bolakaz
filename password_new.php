<?php
include 'session.php';

$token = trim((string)($_GET['code'] ?? ''));
$userId = (int)($_GET['user'] ?? 0);
$path = 'password_reset.php?code=' . urlencode($token) . '&user=' . $userId;

if (!isset($_POST['reset'])) {
    $_SESSION['error'] = 'Set your new password first.';
    header('location: ' . $path);
    exit();
}

$password = (string)($_POST['password'] ?? '');
$repassword = (string)($_POST['repassword'] ?? '');

if (strlen($password) < 8) {
    $_SESSION['error'] = 'Password must be at least 8 characters.';
    header('location: ' . $path);
    exit();
}

if ($password !== $repassword) {
    $_SESSION['error'] = 'Passwords did not match.';
    header('location: ' . $path);
    exit();
}

if ($token === '' || $userId <= 0) {
    $_SESSION['error'] = 'Invalid or expired reset link.';
    header('location: password_forgot.php');
    exit();
}

$conn = $pdo->open();

try {
    $stmt = $conn->prepare("SELECT id, reset_code FROM users WHERE id=:id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !app_validate_reset_code((string)($row['reset_code'] ?? ''), $token)) {
        $_SESSION['error'] = 'Invalid or expired reset link.';
        header('location: password_forgot.php');
        exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE users SET password=:password, reset_code='' WHERE id=:id");
    $update->execute(['password' => $hashed, 'id' => (int)$row['id']]);

    $_SESSION['success'] = 'Password successfully reset. You can now sign in.';
    header('location: signin');
    exit();
} catch (PDOException $e) {
    $_SESSION['error'] = 'Unable to reset password right now.';
    header('location: ' . $path);
    exit();
} finally {
    $pdo->close();
}
