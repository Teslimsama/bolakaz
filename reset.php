<?php
include 'session.php';
require_once __DIR__ . '/lib/mailer.php';
require_once __DIR__ . '/lib/customer_accounts.php';
require_once __DIR__ . '/lib/sync.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $_SESSION['error'] = 'Enter your email address to continue.';
    header('location: password_forgot');
    exit();
}

$email = trim((string)($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Enter a valid email address.';
    header('location: password_forgot');
    exit();
}

$conn = $pdo->open();

try {
    $stmt = $conn->prepare("SELECT id, email, status, type, account_state, is_placeholder_email FROM users WHERE email=:email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && app_customer_can_login($conn, $user)) {
        $resetCode = app_create_reset_code(3600);
        $conn->beginTransaction();
        $updateStmt = $conn->prepare("UPDATE users SET reset_code=:code WHERE id=:id");
        $updateStmt->execute(['code' => $resetCode, 'id' => (int)$user['id']]);
        sync_enqueue_or_fail($conn, 'users', (int) $user['id']);
        $conn->commit();

        $token = explode('.', $resetCode, 2)[0];
        $baseUrl = app_base_url();
        $resetUrl = $baseUrl . '/password_reset?code=' . urlencode($token) . '&user=' . urlencode((string)$user['id']);

        $subject = 'Reset your Bolakaz password';
        $contentHtml = '<p>We received a request to reset your password.</p>
            <p>This secure link expires in <strong>60 minutes</strong>.</p>
            <p>If the button does not open, copy this URL into your browser:</p>
            <p><a href="' . e($resetUrl) . '" style="color:#128278;word-break:break-all;">' . e($resetUrl) . '</a></p>
            <p>If you did not request this, you can ignore this email.</p>';
        $htmlBody = app_email_template(
            'Password Reset Request',
            'Use the secure link below to choose a new password.',
            $contentHtml,
            'Reset Password',
            $resetUrl
        );
        $textBody = "Password Reset Request\n\nUse this link within 60 minutes:\n" . $resetUrl . "\n\nIf you did not request this, ignore this email.";
        app_send_email($email, $subject, $htmlBody, $textBody);
    }

    // Always show the same success message to prevent account enumeration.
    unset($_SESSION['error']);
    $_SESSION['success'] = 'If an account exists with that email, a reset link has been sent.';
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    unset($_SESSION['success']);
    $_SESSION['error'] = 'Unable to process request right now. Please try again later.';
}

$pdo->close();
header('location: password_forgot');
exit();
