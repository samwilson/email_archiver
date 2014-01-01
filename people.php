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

/*
$form = new HTML_QuickForm();
$form->setDefaults($defaults);
$form->addElement('header', null, $header);
$form->addElement('hidden', 'id');
if (isset($_GET['person_text'])) {
	$form->addElement('static',null,'Person text: ', htmlentities($_GET['person_text']));
}
$form->addElement('text','name','Name: ',array('class'=>'span-20'));
$form->addElement('text','email_address', 'Email address: ', array('class'=>'span-20'));
$notesArea = new HTML_QuickForm_textarea('notes', 'Notes: ');
$notesArea->setAttribute('class','span-20');
$notesArea->setRows(5);
$notesArea->setCols(80);
$form->addElement($notesArea);
$form->addElement('submit','save', 'Save');
$page->addBodyContent("<div class='span-24 last'>".$form->toHtml()."</div>");

if ($form->isSubmitted() && $form->validate()) {
	$db->save('people', $form->getSubmitValues());
}*/

/*******************************************************************************
 * List people.
 ******************************************************************************/

$list = '<ul>';
foreach ($db->query("SELECT * FROM people ORDER BY name ASC") as $p) {
	$list .= '<li><a href="people.php?edit&id='.$p['id'].'">'.$p['name'].'</a> ';
	if (!empty($p['email_address'])) $list .= '&lt;'.$p['email_address'].'&gt; ';
	if (!empty($p['notes'])) $list .= $p['notes'];
	$list .= ' <a class="noprint delete" href="?table_name=people&delete&id='.$p['id'].'">[d]</a></li>';
}
$list .= '</ul>';
if (isset($_GET['action']) && $_GET['action']=='print') {
	$page->setBody($list);
} else {
	$page->addBodyContent("<p><a href='?action=print'>[Print view]</a></p>".$list);
}


/*******************************************************************************
 * Clean up and output.
 ******************************************************************************/

$page->addBodyContent('</div><!-- end div.container -->');
$page->display();

