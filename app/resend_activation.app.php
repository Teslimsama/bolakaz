<?php
include '../session.php';
require_once __DIR__ . '/../lib/mailer.php';
require_once __DIR__ . '/../lib/customer_accounts.php';
require_once __DIR__ . '/../lib/sync.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['resend_activation'])) {
    $_SESSION['error'] = 'Invalid activation resend request.';
    header('location: ../signin');
    exit();
}

$email = trim((string)($_POST['email'] ?? $_SESSION['pending_activation_email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Enter a valid email to resend activation.';
    header('location: ../signin');
    exit();
}

$conn = $pdo->open();
try {
    $selectColumns = app_customer_select_columns($conn, ['id', 'email', 'firstname', 'status', 'type']);
    $stmt = $conn->prepare("SELECT {$selectColumns} FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && app_customer_can_resend_activation($conn, $user)) {
        $code = bin2hex(random_bytes(16));
        $conn->beginTransaction();
        $update = $conn->prepare("UPDATE users SET activate_code = :code WHERE id = :id");
        $update->execute(['code' => $code, 'id' => (int)$user['id']]);
        sync_enqueue_or_fail($conn, 'users', (int) $user['id']);
        $conn->commit();

        $activateUrl = app_base_url() . '/activate?code=' . urlencode($code) . '&user=' . urlencode((string)$user['id']);
        $subject = 'Activate your Bolakaz account';
        $contentHtml = '
            <p>Hi ' . e((string)($user['firstname'] ?? 'there')) . ',</p>
            <p>Your account is almost ready. Confirm your email to activate it.</p>
            <p>If the button does not open, copy this URL into your browser:</p>
            <p><a href="' . e($activateUrl) . '" style="color:#128278;word-break:break-all;">' . e($activateUrl) . '</a></p>
        ';
        $htmlBody = app_email_template(
            'Account Activation Required',
            'Confirm your email to activate your account.',
            $contentHtml,
            'Activate Account',
            $activateUrl
        );
        $textBody = "Activate your Bolakaz account\n\nUse this link:\n" . $activateUrl;

        app_send_email((string)$user['email'], $subject, $htmlBody, $textBody);
    }

    $_SESSION['success'] = 'If your account exists and is pending activation, a new activation email has been sent.';
    unset($_SESSION['pending_activation_email']);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Resend activation failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to resend activation right now. Please try again later.';
}

$pdo->close();
header('location: ../signin');
exit();
