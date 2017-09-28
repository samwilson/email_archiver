<?php

namespace Samwilson\EmailArchiver;

use Exception;
use Slim\Http\Request;
use Slim\Http\Response;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class EmailsController extends Controller {
    
    public function home(Request $request, Response $response, $args)
    {
        if (!empty($_REQUEST['with'])) $with = $_REQUEST['with']; else $with = false;
        if (!empty($_REQUEST['year'])) $year = $_REQUEST['year']; else $year = false;

        $mainUser = $this->db->query('SELECT * FROM people WHERE id = '.MAIN_USER_ID);
        $mainUser = $mainUser->fetch();
        if (!$mainUser) {
            throw new Exception("Main user not found");
        }

        $sql = 'SELECT id, name, email_address FROM people WHERE id = :id';
        $to = $this->db->prepare($sql);
        $to->execute([':id' => $with]);
        $to = $to->fetch();

        /*******************************************************************************
         * Send reply and save to DB.
         ******************************************************************************/
        if (isset($_POST['send'])) {
            $this->sendMessage($to);
        }

        /*******************************************************************************
         * Get years.
         ******************************************************************************/
        $years = array();
        $res = $this->db->query("SELECT YEAR(date_and_time) AS year FROM emails GROUP BY year");
        $res->execute();
        foreach ($res->fetchAll() as $y) {
            $years[$y['year']] = $y['year'];
        }
        if (count($years)==0) {
            echo "<p>Nothing found.</p>";
        }


        /*******************************************************************************
         * Get people
         ******************************************************************************/
        $ppl = $this->db->query("SELECT id, name FROM people ORDER BY name ASC")->fetchAll();
        $people = array();
        foreach ($ppl as $person) {
            $people[$person['id']] = $person['name'];
        }


        /*******************************************************************************
         * Navigation form stuff.
         ******************************************************************************/
        $email_count = $this->db->query('SELECT COUNT(*) FROM emails LIMIT 1')->fetchColumn();
        $this->view->render($response, 'email_nav_form.php');
//        ob_start();
//        require_once 'views/email_nav_form.php';
//        $nav = ob_get_clean();
//        $page->addBodyContent($nav);


        /*******************************************************************************
         * list emails
         ******************************************************************************/

        if ($year || $with) {

            $sql = "SELECT * FROM emails WHERE YEAR(date_and_time)=:year ";
            $params = array(':year'=>$year);
            if ($with) {
                $sql .= "AND (to_id = :with OR from_id = :with) ";
                $params[':with'] = $with;
            }
            $sql .= "ORDER BY date_and_time ASC";

            $emails = $db->prepare($sql);
            $emails->execute($params);
            $emails = $emails->fetchAll();
            $last_subject = '';
            $last_body = '';
            $last_date = '';
            $last_from_id = '';
            $page->addBodyContent("<p class='centre'>Showing ".count($emails)." emails.</p>");
            if (count($emails) > 0) {
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
            //$sql = 'SELECT CONCAT(name," <",email_address,">") FROM people WHERE id = :id';
            $sql = 'SELECT id, name, email_address FROM people WHERE id = :id';
            //$to = $db->('people',$with,"CONCAT(name,' <',email_address,'>')");
            $to = $db->prepare($sql);
            $to->execute(array(':id'=>$with));
            $to = $to->fetch(); // htmlentities($to->fetchColumn());

            // Subject
            if (stristr($last_subject,'re') === FALSE) {
                $subject = 'Re: '.$last_subject;
            } else {
                $subject = $last_subject;
            }

            ob_start();
            require 'views/email_form.php';
            $email_form = ob_get_clean();
            $page->addBodyContent($email_form);
        }


        /*******************************************************************************
         * who still to reply to
         ******************************************************************************/

        $page->addBodyContent("<h2>People:</h2><ul class='columnar'>");
        foreach ($people as $pid=>$name) {
            if ($pid != MAIN_USER_ID) {
                // Get information about the last email from this person.
                $sql = "SELECT from_id, to_id, YEAR(date_and_time) AS year FROM emails
                WHERE to_id = :pid OR from_id = :pid
                ORDER BY date_and_time DESC LIMIT 1";
                $unanswered = $db->prepare($sql);
                $unanswered->execute(array(':pid'=>$pid));
                $unanswered = $unanswered->fetch();

                if ($unanswered) { // If there is any last email.
                    $from_id = $unanswered['from_id'];
                    $to_id = $unanswered['to_id'];
                    $year = $unanswered['year'];
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
        $page->addBodyContent("</ul>");
        
        return $this->view->render($response, 'emails.html.twig');
    }

    protected function sendMessage()
    {
        $body = $_POST['message_body'];
        if (!empty($_POST['last_body'])) {
            $body .= "\n\n
-------- Previous message --------
-- Date: ".$_POST['last_date']."
-- From: ".$to['name']." <".$to['email_address'].">
--   To: ".$mainUser['name']." <".$mainUser['email_address'].">
--------

".wordwrap($_POST['last_body'],78)."

-------- End of previous message --------
";
        }
    
        // Send the message
        $message = Swift_Message::newInstance($_POST['subject'], $body)
            ->setFrom(array($mainUser['email_address'] => $mainUser['name']))
            ->setTo(array($to['email_address'] => $to['name']));
        $transport = Swift_SmtpTransport::newInstance($mail_server['smtp_server'], $mail_server['smtp_port'])
            ->setUsername($_SESSION['username'].$mail_server['suffix'])
            ->setPassword($_SESSION['password']);
        $mailer = Swift_Mailer::newInstance($transport);
        $result = $mailer->send($message);
        if (!$result) {
            echo 'The mail could not be sent.';
            exit(1);
        }

        // Save the message
        $sql = 'INSERT INTO emails '
            . ' SET from_id=:from, to_id=:to, date_and_time=:date_and_time,'
            . ' subject=:subject, message_body=:message_body';
        $insert = $this->db->prepare($sql);
        $insert->bindParam(':from', $mainUser['id']);
        $insert->bindParam(':to', $with);
        $insert->bindParam(':date_and_time', date('Y-m-d H:i:s'));
        $insert->bindParam(':subject', $_POST['subject']);
        $insert->bindParam(':message_body', $_POST['message_body']);
        if (!$insert->execute()) {
            print_r($insert->errorInfo());
            $exit(1);
        }

        header("Location:".$_SERVER['PHP_SELF']."?with=$with&year=$year#reply-form");
    }
}