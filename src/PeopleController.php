<?php
include 'common.php';

$page->setTitle('People');

/*******************************************************************************
 * Save person
 ******************************************************************************/

if (isset($_POST['save']) && !empty($_POST['name'])) {
	if (isset($_POST['id'])) {
		$save = $db->prepare('UPDATE people SET name=:name, email_address=:email_address, notes=:notes WHERE id=:id');
		$save->bindParam(':id', $_POST['id']);
	} else {
		$save = $db->prepare('INSERT INTO people SET name=:name, email_address=:email_address, notes=:notes');
	}
	$save->bindParam(':name', $_POST['name']);
	$save->bindParam(':email_address', $_POST['email_address']);
	$save->bindParam(':notes', $_POST['notes']);
	if (!$save->execute()) {
            $page->addBodyContent('<p class="message error">Unable to save. Error: <code>'.  array_pop($save->errorInfo()).'</code></p>');
        }
}


/*******************************************************************************
 * Construct possible person.
 ******************************************************************************/

if (isset($_GET['person_text'])) {
	$email_address_pattern = "/[a-zA-Z0-9\.\-_]*@[a-zA-Z0-9\.\-_]*/i";
	preg_match("/^\"?(\S*)/i",$_GET['person_text'],$first_name);
	preg_match("/\s(\S*)/i",$_GET['person_text'],$surname);
	preg_match($email_address_pattern,$_GET['person_text'],$email_address);
}
$name = '';
if (isset($first_name[1])) {
	$name = $first_name[1];
}
if (isset($surname[1])) {
	$name .= ' '.$surname[1];
}
$person = array('name'=>$name, 'email_address'=>'', 'notes'=>'');
if (isset($email_address[0])) {
	$person['email_address'] = $email_address[0];
} else {
	$person['email_address'] = '';
}

if (isset($_GET['edit']) && is_numeric($_GET['id'])) {
	$person = $db->prepare("SELECT id, name, email_address, notes FROM people WHERE id=:id LIMIT 1");
	$person->bindParam(':id', $_GET['id']);
	$person->execute();
	$person = $person->fetch();
	$header = 'Editing person #'.$_GET['id'];
} else {
	$header = 'Enter new person data';
}

/*******************************************************************************
 * New person form.
 ******************************************************************************/

ob_start();
require 'views/person_form.php';
$person_form = ob_get_clean();
$page->addBodyContent($person_form);

/*******************************************************************************
 * List people.
 ******************************************************************************/

$list = '<ol>';
foreach ($db->query("SELECT * FROM people ORDER BY name ASC") as $p) {
	$list .= '<li><a href="people.php?edit&id='.$p['id'].'">'.$p['name'].'</a> ';
	if (!empty($p['email_address'])) $list .= '&lt;'.$p['email_address'].'&gt; ';
	if (!empty($p['notes'])) $list .= $p['notes'];
	$list .= ' <a class="noprint delete" href="?table_name=people&delete&id='.$p['id'].'">[d]</a></li>';
}
$list .= '</ol>';
$page->addBodyContent($list);


/*******************************************************************************
 * Clean up and output.
 ******************************************************************************/

$page->addBodyContent('</div><!-- end div.container -->');
$page->display();

