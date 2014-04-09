<?php

require_once 'config.php';
require 'vendor/autoload.php';

session_start();

if (basename($_SERVER['PHP_SELF']) != 'login.php') {
    $auth_ok = checkAuth() && isset($_SESSION['logged_in']);
    if (!$auth_ok) {
        header('Location:login.php');
        exit();
    }
}


function checkAuth() {
    $timeout = 60 * 30; // In seconds, i.e. 30 minutes.
    $fingerprint = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
    $has_expired = isset($_SESSION['last_active']) && $_SESSION['last_active'] < (time() - $timeout);
    //var_dump($has_expired);
    if ( $has_expired
         || (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint']!=$fingerprint)
         || isset($_GET['logout'])
        ) {
        setcookie(session_name(), '', time()-3600, '/');
        session_destroy();
        return false;
    }
    session_regenerate_id(); 
    $_SESSION['last_active'] = time();
    $_SESSION['fingerprint'] = $fingerprint;
    return true;
}


// Database
$db = new PDO($dsn, $db_user, $db_pass);


// Set up HTML page object.
require_once 'HTML/Page2.php';
$page = new HTML_Page2();
$page->setDoctype('XHTML 1.0 Strict');
$page->addStylesheet('style.css', 'text/css');
$page->setTitle(SITETITLE);


// Javascript
$page->addScript('scripts.js');
$page->setAttribute('onload', 'onBodyLoad()');


// Set up structure for BlueprintCSS.
$page->addBodyContent('<div class="container">');
// Don't forget to add the following at the end of every script:
#$page->addBodyContent('</div><!-- end div.container -->');
#$page->display();


// Get main navigation bar
ob_start();
require_once 'views/nav.php';
$nav = ob_get_clean();
$page->addBodyContent($nav);
