<?php
declare(strict_types=1);

return [
    'from_email' => 'your-gmail-address@gmail.com',
    'from_name' => 'Shoes',
    'php_mail_from_email' => 'no-reply@your-domain.com',
    'php_mail_extra_parameters' => '-f no-reply@your-domain.com',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-gmail-address@gmail.com',
    'smtp_password' => 'your_gmail_app_password_without_spaces',
    'smtp_encryption' => 'tls',
    'smtp_timeout' => 15,
    'allow_php_mail' => true,
    'log_emails' => true,
];
