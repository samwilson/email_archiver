<?php

// The title of the whole site.  Used in a bunch of places.  Or one, at least.
define('SITETITLE', 'Email Archiver');

// Hide all errors.  Set this to E_ALL if you're getting a blank screen and want
// to know why; after you fixed whatever's wrong (usually database connection or
// PEAR requirements) set it back to 0 so that no error messages are show to the
// public.
error_reporting(0);

// Timezone.
// See http://php.net/manual/en/function.date-default-timezone-set.php for a
// list of valid timezones.
date_default_timezone_set('Time/Zone');

// The Data Source Name should be usable with PEAR's MDB2 class.  One of these:
$dsn = "mysql://username:password@example.org/database_name";
$dsn = array(
	'phptype'  => 'mysql',
	'username' => 'username',
	'password' => 'password',
	'hostspec' => 'localhost',
	'protocol' => 'unix',
	'socket'   => '/tmp/mysql.sock',
	'database' => 'database_name',
);

// If the system-wide copy of PEAR doesn't contain all we need, you might need
// to install a local copy.  Enter the path to it here.
set_include_path(get_include_path() . PATH_SEPARATOR . "/home/user/pear/php/");

// The absolute URL to the root of the website, without a trailing slash.
define('WEBROOT', 'http://example.org/sub/directory');

// Mail server information.
$mail_server['server']   = 'mail.example.org';
$mail_server['port']     = 143;
$mail_server['username'] = 'user@example.org';
$mail_server['password'] = 'p4zzw0rD';
$mail_server['inbox']    = 'INBOX.ToArchive';

// The main user must have a person record; enter that record's ID here.
define('MAIN_USER_ID', 1);

// Configure usernames and passwords.  This will be replaced with a more secure
// system soon.
$users = array('admin' => sha1('p4zzw0rD'));
