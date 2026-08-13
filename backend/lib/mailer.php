<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function mail_log_path(): string
{
    return root_path('storage/mail.log');
}

function log_app_email(string $toEmail, string $subject, string $htmlBody, string $textBody): void
{
    $directory = dirname(mail_log_path());
    if (!is_dir($directory)) {
        @mkdir($directory, 0777, true);
    }

    $entry = implode(PHP_EOL, [
        '--- EMAIL ' . date('Y-m-d H:i:s') . ' ---',
        'To: ' . $toEmail,
        'Subject: ' . $subject,
        'Text:',
        $textBody,
        'HTML:',
        $htmlBody,
        '',
    ]);

    @file_put_contents(mail_log_path(), $entry, FILE_APPEND);
}

function log_mail_status(string $message): void
{
    $directory = dirname(mail_log_path());
    if (!is_dir($directory)) {
        @mkdir($directory, 0777, true);
    }

    @file_put_contents(
        mail_log_path(),
        '[MAIL ' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function send_app_email(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $textBody = $textBody !== '' ? $textBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $htmlBody));
    $fromEmail = (string) app_config('mail.from_email', 'no-reply@example.com');
    $fromName = (string) app_config('mail.from_name', (string) app_config('app.name', 'Store'));
    $phpMailFromEmail = (string) app_config('mail.php_mail_from_email', $fromEmail);
    $phpMailExtraParameters = (string) app_config('mail.php_mail_extra_parameters', '');
    $smtpHost = (string) app_config('mail.smtp_host', '');
    $smtpUsername = (string) app_config('mail.smtp_username', '');
    $smtpPassword = (string) app_config('mail.smtp_password', '');
    $smtpReady = $smtpHost !== '' && ($smtpUsername === '' || $smtpPassword !== '');
    $sent = false;
    $lastError = '';

    if ($smtpHost !== '' && !$smtpReady) {
        $lastError = 'SMTP skipped because a host/username is configured but the SMTP password is empty. Use a Gmail App Password or remove SMTP settings to use PHP mail().';
        log_mail_status($lastError);
    }

    if ($smtpReady && class_exists(PHPMailer::class)) {
        try {
            $mailer = new PHPMailer(true);
            $mailer->CharSet = 'UTF-8';
            $mailer->Timeout = (int) app_config('mail.smtp_timeout', 15);
            $mailer->isSMTP();
            $mailer->Host = $smtpHost;
            $mailer->Port = (int) app_config('mail.smtp_port', 587);
            $mailer->SMTPAuth = $smtpUsername !== '';
            $mailer->Username = $smtpUsername;
            $mailer->Password = $smtpPassword;
            $encryption = strtolower((string) app_config('mail.smtp_encryption', 'tls'));
            if (in_array($encryption, ['tls', 'ssl'], true)) {
                $mailer->SMTPSecure = $encryption;
            }

            $mailer->setFrom($fromEmail, $fromName);
            $mailer->addAddress($toEmail, $toName);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlBody;
            $mailer->AltBody = $textBody;
            $sent = $mailer->send();
        } catch (PHPMailerException|Throwable $throwable) {
            $lastError = 'SMTP send failed: ' . $throwable->getMessage();
            log_mail_status($lastError);
            $sent = false;
        }
    }

    if (!$sent && (bool) app_config('mail.allow_php_mail', true)) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $phpMailFromEmail . '>',
            'Reply-To: ' . $fromEmail,
        ];
        $sent = $phpMailExtraParameters !== ''
            ? @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers), $phpMailExtraParameters)
            : @mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
        if (!$sent) {
            $lastError = $lastError !== '' ? $lastError . ' PHP mail() also failed.' : 'PHP mail() failed.';
            log_mail_status($lastError);
        }
    }

    if (!$sent || (bool) app_config('mail.log_emails', true)) {
        log_app_email($toEmail, $subject, $htmlBody, $textBody);
    }

    return $sent;
}

function send_verification_email(array $user, string $token): bool
{
    $link = backend_url('auth/verify_email.php?token=' . rawurlencode($token));
    $subject = 'Verify your ' . app_config('app.name', 'store') . ' account';
    $html = '<p>Hello ' . htmlspecialchars((string) $user['name']) . ',</p>'
        . '<p>Please verify your customer account by clicking the link below:</p>'
        . '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>'
        . '<p>This link will expire soon. If you did not create an account, you can ignore this email.</p>';
    $text = "Hello {$user['name']},\n\nVerify your account using this link:\n{$link}\n";

    return send_app_email((string) $user['email'], (string) $user['name'], $subject, $html, $text);
}

function send_password_reset_email(array $user, string $token): bool
{
    $link = backend_url('auth/reset_password.php?token=' . rawurlencode($token));
    $subject = 'Reset your ' . app_config('app.name', 'store') . ' password';
    $html = '<p>Hello ' . htmlspecialchars((string) $user['name']) . ',</p>'
        . '<p>We received a request to reset your password. Click the link below:</p>'
        . '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>'
        . '<p>If you did not request this, you can ignore this email.</p>';
    $text = "Hello {$user['name']},\n\nReset your password using this link:\n{$link}\n";

    return send_app_email((string) $user['email'], (string) $user['name'], $subject, $html, $text);
}
