<?php

require_once 'common.php';

$login_failed = FALSE;

if (isset($_POST['username'])) {

    require_once 'Net/IMAP.php';
    $imap = new Net_IMAP($mail_server['imap_server'], $mail_server['imap_port']);
    $login = $imap->login($_POST['username'].$mail_server['suffix'], $_POST['password'], true, false);

    if ($login instanceof PEAR_Error) {
        $login_failed = true;
    } else {
        // User exists; log user in.
        $fingerprint = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        $_SESSION['last_active'] = time();
        $_SESSION['fingerprint'] = $fingerprint;
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['password'] = $_POST['password'];
        header('Location:index.php');
    }
}

require 'views/login.php';
