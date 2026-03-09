<?php

if (!function_exists('app_mail_env')) {
    function app_mail_env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if (!is_string($value)) {
            return $default;
        }

        $value = trim($value);
        return $value === '' ? $default : $value;
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $configured = rtrim(app_mail_env('APP_URL'), '/');
        if ($configured !== '') {
            return $configured;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');
    }
}

if (!function_exists('app_email_template')) {
    function app_email_template(
        string $title,
        string $intro,
        string $contentHtml,
        ?string $buttonLabel = null,
        ?string $buttonUrl = null
    ): string {
        $brand = 'Bolakaz';
        $year = date('Y');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeIntro = htmlspecialchars($intro, ENT_QUOTES, 'UTF-8');
        $safeButtonLabel = htmlspecialchars((string)$buttonLabel, ENT_QUOTES, 'UTF-8');
        $safeButtonUrl = htmlspecialchars((string)$buttonUrl, ENT_QUOTES, 'UTF-8');

        $buttonHtml = '';
        if ($buttonLabel !== null && $buttonUrl !== null && $buttonLabel !== '' && $buttonUrl !== '') {
            $buttonHtml = '
                <tr>
                  <td style="padding: 16px 0 8px 0;">
                    <a href="' . $safeButtonUrl . '" style="display:inline-block;background:#128278;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:10px;">' . $safeButtonLabel . '</a>
                  </td>
                </tr>
            ';
        }

        return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>' . $safeTitle . '</title>
</head>
<body style="margin:0;padding:0;background:#f3f5f7;font-family:Segoe UI,Arial,sans-serif;color:#142126;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $safeIntro . '</div>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f5f7;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e4eaee;">
          <tr>
            <td style="background:linear-gradient(120deg,#0e1b21,#128278);padding:20px 24px;color:#ffffff;">
              <h1 style="margin:0;font-size:22px;line-height:1.2;">' . $brand . '</h1>
              <p style="margin:6px 0 0 0;font-size:13px;opacity:.92;">Curated premium fashion and lifestyle</p>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 24px;">
              <h2 style="margin:0 0 8px 0;font-size:24px;line-height:1.2;color:#142126;">' . $safeTitle . '</h2>
              <p style="margin:0 0 12px 0;font-size:15px;line-height:1.6;color:#3d4f56;">' . $safeIntro . '</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="font-size:15px;line-height:1.7;color:#20343d;">' . $contentHtml . '</td>
                </tr>
                ' . $buttonHtml . '
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 24px;background:#f8fbfc;border-top:1px solid #e7eef1;font-size:12px;color:#6a7a82;line-height:1.6;">
              <p style="margin:0;">Need help? Reply to this email or contact support.</p>
              <p style="margin:4px 0 0 0;">&copy; ' . $year . ' ' . $brand . '. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
    }
}

if (!function_exists('app_send_email')) {
    function app_send_email(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            $autoload = __DIR__ . '/../vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }

        $fromEmail = app_mail_env('MAIL_FROM', 'info@bolakaz.unibooks.com.ng');
        $fromName = app_mail_env('MAIL_FROM_NAME', 'Bolakaz');
        $smtpHost = app_mail_env('SMTP_HOST');
        $smtpPort = (int)app_mail_env('SMTP_PORT', '587');
        $smtpUser = app_mail_env('SMTP_USERNAME');
        $smtpPass = app_mail_env('SMTP_PASSWORD');
        $smtpEnc = strtolower(app_mail_env('SMTP_ENCRYPTION', 'tls'));

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            if ($smtpHost !== '') {
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->Port = $smtpPort > 0 ? $smtpPort : 587;
                $mail->SMTPAuth = ($smtpUser !== '' && $smtpPass !== '');
                if ($mail->SMTPAuth) {
                    $mail->Username = $smtpUser;
                    $mail->Password = $smtpPass;
                }
                if ($smtpEnc === 'ssl' || $smtpEnc === 'smtps') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                } elseif ($smtpEnc === 'tls' || $smtpEnc === 'starttls') {
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
            } else {
                $mail->isMail();
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyToEmail, $replyToName ?: '');
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody !== '' ? $textBody : trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));
            $mail->XMailer = 'Bolakaz Mailer';
            return $mail->send();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
