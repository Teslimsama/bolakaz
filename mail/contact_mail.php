<?php
require_once __DIR__ . '/../lib/mailer.php';

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$mSubject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $mSubject === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    exit('Invalid contact payload');
}

$contactTo = app_mail_env('MAIL_CONTACT_TO', app_mail_env('MAIL_FROM', 'info@bolakaz.unibooks.com.ng'));

$adminSubject = 'New Contact Message: ' . $mSubject;
$adminContent = '
    <p>You received a new contact request.</p>
    <p><strong>Name:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '<br>
    <strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>
    <strong>Subject:</strong> ' . htmlspecialchars($mSubject, ENT_QUOTES, 'UTF-8') . '</p>
    <p><strong>Message:</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>
';
$adminHtml = app_email_template(
    'New Contact Request',
    'A visitor submitted the contact form.',
    $adminContent
);
$adminText = "New contact request\nName: $name\nEmail: $email\nSubject: $mSubject\n\nMessage:\n$message";

$adminSent = app_send_email($contactTo, $adminSubject, $adminHtml, $adminText, $email, $name);
if (!$adminSent) {
    http_response_code(500);
    exit('Unable to send message');
}

$userSubject = 'We received your message';
$userContent = '
    <p>Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
    <p>Thanks for contacting Bolakaz. Our team will get back to you shortly.</p>
    <p>Reference subject: <strong>' . htmlspecialchars($mSubject, ENT_QUOTES, 'UTF-8') . '</strong></p>
';
$userHtml = app_email_template(
    'Message Received',
    'Your contact request has been received by our support team.',
    $userContent,
    'Visit Store',
    app_base_url()
);
$userText = "Hi $name,\n\nWe received your message with subject \"$mSubject\". Our team will respond shortly.";
app_send_email($email, $userSubject, $userHtml, $userText);

http_response_code(200);
echo 'ok';
