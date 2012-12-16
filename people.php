<?php
include 'common.php';

$page->setTitle('People');

 
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
$defaults['name'] = $name;
if (isset($email_address[0])) {
	$defaults['email_address'] = $email_address[0];
} else {
	$defaults['email_address'] = '';
}

if (isset($_GET['edit']) && is_numeric($_GET['id'])) {
	$defaults = $db->fetchAll("SELECT * FROM people WHERE id='".$db->esc($_GET['id'])."' LIMIT 1");
	$defaults = $defaults[0];
	$header = 'Editing person #'.$_GET['id'];
} else {
	$defaults = array();
	$header = 'Enter new person data';
}

/*******************************************************************************
 * New person form.
 ******************************************************************************/

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
}

/*******************************************************************************
 * List people.
 ******************************************************************************/

$list = '<ul>';
foreach ($db->fetchAll("SELECT * FROM people ORDER BY name ASC") as $p) {
	$list .= '<li><a href="people.php?edit&id='.$p['id'].'">'.$p['name'].'</a> ';
	if (!empty($p['email_address'])) $list .= '&lt;'.$p['email_address'].'&gt; ';
	if (!empty($p['notes'])) $list .= $p['notes'];
	$list .= ' <a class="noprint delete" href="?table_name=people&delete&id='.$p['id'].'">[d]</a></li>';
}
$list .= '</ul>';
if (isset($_GET['action']) && $_GET['action']=='print') {
	$css->parseString("
		.noprint {display:none}
		body{font-size:smaller}
		a {color:black; text-decoration:none}
	");
	$page->setBody($list);
} else {
	$page->addBodyContent("<p><a href='?action=print'>[Print view]</a></p>".$list);
}


/*******************************************************************************
 * Clean up and output.
 ******************************************************************************/

$page->addBodyContent('</div><!-- end div.container -->');
$page->display();

