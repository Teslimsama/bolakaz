<?php
include 'session.php';
$conn = $pdo->open();

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));

$notice = [
    'type' => 'danger',
    'title' => 'Newsletter Update',
    'message' => 'Unable to process your subscription right now. Please try again.',
];

if ($name === '' || $email === '') {
    $notice['message'] = 'Please provide both name and email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $notice['message'] = 'Please enter a valid email address.';
} else {
    try {
        $checkStmt = $conn->prepare("SELECT COUNT(*) AS total FROM newsletter WHERE email = :email");
        $checkStmt->execute(['email' => $email]);
        $exists = (int)($checkStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;

        if ($exists) {
            $notice = [
                'type' => 'info',
                'title' => 'Already Subscribed',
                'message' => 'This email is already on our newsletter list.',
            ];
        } else {
            $stmt = $conn->prepare("INSERT INTO newsletter (email, name) VALUES (:email, :name)");
            $stmt->execute(['email' => $email, 'name' => $name]);
            $notice = [
                'type' => 'success',
                'title' => 'Subscription Successful',
                'message' => 'You have been subscribed to our newsletter.',
            ];
        }
    } catch (PDOException $e) {
        $notice['message'] = 'We could not save your subscription at the moment.';
    }
}

$_SESSION['newsletter_notice'] = $notice;
$pdo->close();

$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$path = (string)parse_url($referer, PHP_URL_PATH);
$query = (string)parse_url($referer, PHP_URL_QUERY);
$host = (string)parse_url($referer, PHP_URL_HOST);
$currentHost = (string)($_SERVER['HTTP_HOST'] ?? '');

if ($host !== '' && $currentHost !== '' && strcasecmp($host, $currentHost) !== 0) {
    $path = '';
}

if ($path === '') {
    header('Location: index');
    exit();
}

$redirect = $path;
if ($query !== '') {
    $redirect .= '?' . $query;
}

header('Location: ' . $redirect);
exit();
