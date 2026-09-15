<?php
/**
 * Copy this file to config.php and fill in your Gmail App Password.
 *
 * How to create a Gmail App Password:
 * 1. Enable 2-Step Verification on helperhomeahmedabad@gmail.com
 * 2. Google Account → Security → App passwords → Generate
 * 3. Paste the 16-character password below (SMTP_PASS)
 */
return [
    'smtp_host'       => 'smtp.gmail.com',
    'smtp_port'       => 587,
    'smtp_secure'     => 'tls',
    'smtp_user'       => 'helperhomeahmedabad@gmail.com',
    'smtp_pass'       => 'YOUR_GMAIL_APP_PASSWORD_HERE',
    'from_email'      => 'helperhomeahmedabad@gmail.com',
    'from_name'       => 'Helper Home',
    'to_email'        => 'helperhomeahmedabad@gmail.com',
    'to_name'         => 'Helper Home Team',
    'reply_to_customer' => true,
];
