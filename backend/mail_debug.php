<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/bootstrap.php';

try {
    require_auth(['admin', 'seller'], true);

    $lastLogLines = [];
    if (is_file(mail_log_path())) {
        $lines = file(mail_log_path(), FILE_IGNORE_NEW_LINES);
        if (is_array($lines)) {
            $lastLogLines = array_slice($lines, -30);
        }
    }

    success_response([
        'mail_function_available' => function_exists('mail'),
        'smtp_host' => (string) app_config('mail.smtp_host', ''),
        'smtp_enabled' => (string) app_config('mail.smtp_host', '') !== '',
        'from_email' => (string) app_config('mail.from_email', ''),
        'php_mail_from_email' => (string) app_config('mail.php_mail_from_email', ''),
        'php_mail_extra_parameters' => (string) app_config('mail.php_mail_extra_parameters', ''),
        'allow_php_mail' => (bool) app_config('mail.allow_php_mail', true),
        'mail_log_writable' => is_writable(dirname(mail_log_path())),
        'last_mail_log_lines' => $lastLogLines,
    ], 'Mail configuration loaded.');
} catch (Throwable $throwable) {
    error_response($throwable->getMessage(), 500);
}
