<?php
require_once 'common.php';

$page->setTitle("Emails");
$css->parseString("div.email {background-color:#eee; margin:1em; padding:1em}");
if (!empty($_REQUEST['with'])) $with = $_REQUEST['with']; else $with = 0;
if (!empty($_REQUEST['year'])) $year = $_REQUEST['year']; else $year = 0;

$mainUser = $db->fetchRow('SELECT * FROM people WHERE id = '.MAIN_USER_ID);

/*******************************************************************************
 * Send reply and save to DB.
 ******************************************************************************/
if (isset($_POST['save']) && $_POST['save']=='Send') {
    $body = $_POST['message_body'];
    if (!empty($_POST['last_body'])) {
        $body .= "\n\n
------------------------------------------------------------------------------
Date: ".$_POST['last_date']."
From: ".$_POST['to']."
  To: ".$mainUser['name']." <".$mainUser['email_address'].">
------------------------------------------------------------------------------

".wordwrap($_POST['last_body'],78)."

------------------------------------------------------------------------------
";
    }
    $headers = "From: ".$mainUser['name']." <".$mainUser['email_address'].">\r\n";
    if (!mail($_POST['to'], $_POST['subject'], $body, $headers)) {
        die("An error occured when sending the email.");
    }
    $db->save('emails', $_POST);
    header("Location:".$_SERVER['PHP_SELF']."?with=".$_POST['to_id']."&year=$year#reply-form");
}

/*******************************************************************************
 * Get years.
 ******************************************************************************/
$years = array();
$res = $db->fetchAll("SELECT YEAR(date_and_time) AS year FROM emails GROUP BY year");
foreach ($res as $y) {
    $years[$y['year']] = $y['year'];
}


/*******************************************************************************
 * Get people
 ******************************************************************************/
$ppl = $db->fetchAll("SELECT id, name FROM people ORDER BY name ASC");
foreach ($ppl as $person) {
    $people[$person['id']] = $person['name'];
}


/*******************************************************************************
 * Navigation form stuff.
 ******************************************************************************/
if (count($years) > 0) {
    $page->addBodyContent("<p class='centre'>LaTeX: | ");
    foreach ($years as $y) {
        $page->addBodyContent(" <a href='latex.php?year=$y' title='$y.tex'>$y</a> | ");
    }
    $page->addBodyContent("</p>");

    $page->addBodyContent("<p class='centre'>Chronological: | ");
    foreach ($years as $y) {
        $page->addBodyContent(" <a href='?year=$y' title='$y.tex'>$y</a> | ");
    }
    $page->addBodyContent("</p>");

    $form = new HTML_QuickForm('','get',$_SERVER['PHP_SELF'].'#reply-form');
    $form->setDefaults(array('year'=>$year, 'with'=>$with));
    $form->addElement('header',null,$db->numRows('emails').' emails in archive');
    $group = array(
        new HTML_QuickForm_select('with','With',$people),
        new HTML_QuickForm_select('year','Year',$years),
        new HTML_QuickForm_submit(null, 'View')
    );
    $form->addGroup($group, null, null);
    $page->addBodyContent($form);
}

/*******************************************************************************
 * list emails
 ******************************************************************************/

if ($year || $with) {
    $css->parseString("
		.email {text-align:left; border:2px solid #CCC}
		.email.from-me {border:2px solid #060}
		.from {color:#CCC;font-variant:small-caps}
		.from.from-me {color:#060}
	");

    $sql = "SELECT * FROM emails WHERE YEAR(date_and_time)=".$db->esc($year)." ";
    if ($with) $sql .= "AND (to_id = ".$db->esc($with)." OR from_id = ".$db->esc($with).") ";
    $sql .= "ORDER BY date_and_time ASC";

    $emails = $db->fetchAll($sql);
    $last_subject = '';
    $last_body = '';
    $last_date = '';
    $last_from_id = '';
    if (count($emails) > 0) {
        $page->addBodyContent("<p class='centre'>Showing ".count($emails)." emails.</p>");
        foreach ($emails as $count=>$email) {
            $email_class = ($email['from_id']==MAIN_USER_ID) ? 'from-me' : '';
            $page->addBodyContent("<div class='email $email_class'><p>");
            if ($count==count($emails)-1) {
                $page->addBodyContent("<a name='reply-form'></a>");
            }
            $page->addBodyContent("
                <span class='from $email_class'>".$people[$email['from_id']]."</span>
            ");
            if (!$with) {
                $page->addBodyContent(" (to ".$people[$email['to_id']].") ");
            }
            $page->addBodyContent(
                    date('l, F jS, g:iA',strtotime($email['date_and_time']))."
				&nbsp;&nbsp;
				<strong>".$email['subject']."</strong> &nbsp;&nbsp;
				<!--span class='small quiet'>
				  <a href='?table_name=emails&edit&id=".$email['id']."'>[e]</a>
				  <del><a href='?table_name=emails&delete&id=".$email['id']."'>[d]</a></del>
				</span-->
				</p><pre>".trim(wordwrap(htmlentities($email['message_body']), 78))."</pre></div>");
            $last_subject = $email['subject'];
            $last_body = $email['message_body'];
            $last_date = $email['date_and_time'];
            $last_from_id = $email['from_id'];
        }
        $page->addBodyContent("<p class='centre'>Showing ".count($emails)." emails.</p>");
    }
}


/*******************************************************************************
 * reply form
 ******************************************************************************/

if ($with) {
    $to = $db->getVar('people',$with,"CONCAT(name,' <',email_address,'>')");
    $replyform = new HTML_QuickForm('', 'post', $_SERVER['PHP_SELF']);
    $replyform->addElement('hidden', 'from_id', MAIN_USER_ID);
    $replyform->addElement('hidden', 'to_id', $with);
    $replyform->addElement('hidden', 'with', $with);
    $replyform->addElement('hidden', 'year', $year);
    $replyform->addElement('hidden', 'correspondent', $with);
    if ($last_from_id != MAIN_USER_ID) {
        $replyform->addElement('hidden','last_body',htmlentities($last_body));
    }
    $replyform->addElement('hidden','last_date',htmlentities($last_date));
    $replyform->addElement('hidden','date_and_time',date('Y-m-d H:i:s'));
    $replyform->addElement('header', null, 'Replying to ' . $to);
    $replyform->addElement('text','to','To: ',array('value'=>$to, 'class'=>'span-10'));
    if (stristr($last_subject,'re')===FALSE) {
        $new_subject = 'Re: '.$last_subject;
    } else {
        $new_subject = $last_subject;
    }
    $replyform->addElement('text','subject','Subject: ',array('value'=>$new_subject, 'class'=>'span-10 title'));
    $bodyelement = new HTML_QuickForm_textarea('message_body', 'Body:', array('class'=>'span-10', 'style'=>'height:24em'));
    $bodyelement->setRows(24);
    $bodyelement->setCols(80);
    $replyform->addElement($bodyelement);
    $replyform->addElement('submit','save','Send');
    $page->addBodyContent('<div class="span-12 prepend-6 append-6 last">'.$replyform->toHtml().'</div>');
}


/*******************************************************************************
 * who still to reply to
 ******************************************************************************/

$page->addBodyContent("<h2>People:</h2><ul class='columnar'>");
foreach ($people as $pid=>$name) {
    if ($pid != MAIN_USER_ID) {
        // Get information about the last email from this person.
        $sql = ("SELECT from_id, to_id, YEAR(date_and_time) AS year FROM emails
		WHERE to_id = ".$db->esc($pid)." OR from_id = ".$db->esc($pid)."
		ORDER BY date_and_time DESC LIMIT 1");
        $unanswered = $db->fetchAll($sql);
        if (isset($unanswered[0])) { // If there is any last email.
            $from_id = $unanswered[0]['from_id'];
            $to_id = $unanswered[0]['to_id'];
            $year = $unanswered[0]['year'];
            // If the last email was incoming and not from the main user.
            if ($from_id != MAIN_USER_ID) {
                $class = 'highlight';
                $with = $from_id;
                //$page->addBodyContent("<li><strong><a style='color:red' href='?with={$from_id}&year={$year}#reply-form'>$name</a></strong></li>");
            } else {
                $with = $to_id;
                $class = '';
                //$page->addBodyContent("<li><a href='?with={$to_id}&year={$year}#reply-form'>$name</a></li>");
                //} elseif () {
                //$page->addBodyContent("<li><a href='?with=$pid&year=".date('Y')."#reply-form'>$name</a></li>");
            }
            $from_id = null;
            $to_id = null;
            //$year = null;
        } else { // There was no last email.
            $year = date('Y');
            $with = $pid;
            $class = '';
        }
        $page->addBodyContent(
            "<li>\n".
            "  <a class='$class' href='?with=$with&year=$year#reply-form'>\n".
            "    $name\n".
            "  </a>\n".
            "</li>\n"
        );
    }
}
$page->addBodyContent("</ul><hr />");

$page->addBodyContent('</div><!-- end div.container -->');
$page->display();

