<?php

// The title of the whole site.  Used in a bunch of places.  Or one, at least.
define('SITETITLE', 'Email Archiver');

// Hide all errors.  Set this to E_ALL if you're getting a blank screen and want
// to know why; after you fixed whatever's wrong (usually database connection or
// PEAR requirements) set it back to 0 so that no error messages are show to the
// public.
error_reporting(0);

// Timezone. For a list of valid timezones
// see http://php.net/manual/en/function.date-default-timezone-set.php
date_default_timezone_set('Time/Zone');

// Database credentials.
// The Data Source Name should be usable with PDO. See http://php.net/pdo
$db_user = 'root';
$db_pass = 'dev123dev';
$dsn = "mysql:dbname=testdb;host=127.0.0.1";

// If the system-wide copy of PEAR doesn't contain all we need, you might need
// to install a local copy.  Enter the path to it here.
set_include_path(get_include_path() . PATH_SEPARATOR . "/home/user/pear/php/");

// Mail server information.
$mail_server['suffix']      = '@example.org';
$mail_server['imap_server'] = 'mail.example.org';
$mail_server['imap_port']   = 143;
$mail_server['smtp_server']   = 'mail.example.org';
$mail_server['smtp_port']     = 587;
$mail_server['inbox']       = 'INBOX.ToArchive';

// The main user must have a person record; enter that record's ID here.
define('MAIN_USER_ID', 1);
