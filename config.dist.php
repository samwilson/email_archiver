<?php

// Show errors?
$displayErrorDetails = false;

// Database. The DSN should be usable with PDO. See https://php.net/pdo
$dbUser = 'email_archiver';
$dbPass = 'correct horse battery staple';
$dbDsn  = "mysql://localhost;dbname=email_archiver";

// Mail server.
$mailServer['username']        = 'example@gmail.com';
$mailServer['password']        = 'staple battery horse correct';
$mailServer['imap_path']       = '{imap.gmail.com:993/imap/ssl}INBOX';
$mailServer['smtp_server']     = 'smtp.gmail.com';
$mailServer['smtp_port']       = 465;
$mailServer['smtp_encryption'] = 'ssl';

// The main user must have a person record; enter that record's ID here.
define('MAIN_USER_ID', 1);

// Main password to get into Email Archiver.
$appPass = '$2y$10$vBTVifnmVKpLb/gwBx54TudGsxDvYFV3vS0kmUII5LZ3oeIBT3lW6';
