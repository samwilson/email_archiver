<?php

// The title of the whole site.  Used in a bunch of places.  Or one, at least.
define('SITETITLE', 'A Channel 27 Site');

// Hide all errors.  Set this to E_ALL if you're getting a blank screen and want
// to know why; after you fixed whatever's wrong (usually database connection or
// PEAR requirements) set it back to 0 so that no error messages are show to the
// public.
error_reporting(0);

// Timezone.
// See http://php.net/manual/en/function.date-default-timezone-set.php for a
// list of valid timezones.
date_default_timezone_set('Time/Zone');

// The DATADIR is used all over the place, hence is global and static.  Note
// that there is NO trailing slash.
define('DATADIR','/home/user/sitedata');

// The Data Source Name should be usable with PEAR's MDB2 class.
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

// If you want to use your site as a OpenID delegate, enter the server and
// delegate details here.
$openid_server = "http://example.org/openid/server";
$openid_delegate = "http://example.org/openid/delegate";

// Mail server information.
$mail_server['server']   = 'mail.example.org';
$mail_server['port']     = 143;
$mail_server['username'] = 'user@example.org';
$mail_server['password'] = 'p4zzw0rD';
$mail_server['inbox']    = 'INBOX.ToArchive';

// The main user must have a person record; enter that record's ID here.
define('MAIN_USER_ID', 1);

// Syndication Feeds
$number_of_feed_enteries_to_show = 40;

define('DRIVER_FOR_IMAGE_TRANSFORM', 'IM');
define('IMAGE_TRANSFORM_IM_PATH', '/usr/local/bin/');
