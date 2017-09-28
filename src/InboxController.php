<?php

namespace Samwilson\EmailArchiver;

use Fetch\Message;
use Fetch\Server;
use Slim\Http\Request;
use Slim\Http\Response;

class InboxController extends Controller
{

    public function inbox(Request $request, Response $response, $args)
    {
        $mailServer = $this->container->get('settings')->get('mailServer');

        $server = new Server($mailServer['imap_server'], $mailServer['imap_port']);
        $server->setAuthentication($mailServer['username'], $mailServer['password']);
        $server->setMailBox($mailServer['inbox']);

//        
//        $message_id = 1;
//        $imap = new Net_IMAP($mail_server['imap_server'], $mail_server['imap_port']);
//        $login = $imap->login($_SESSION['username'].$mail_server['suffix'], $_SESSION['password'], true, false);
//        if ($login instanceof PEAR_Error)
//        {
//            $page->addBodyContent('<p class="error">Unable to connect to mail server.</p>');
//        }
//
//        $mboxSelect = $imap->selectMailbox($mail_server['inbox']);
//        if ($mboxSelect instanceof PEAR_Error) {
//            $page->addBodyContent("<p class='error'>Unable to select mailbox: '" . $mboxSelect->getMessage() . "'.</p>");
//        }

        // Get list of people.
        $ppl = $db->query("SELECT id, name FROM people ORDER BY name ASC");
        $people = array(0 => 'n. i. d.');
        foreach ($ppl as $person) {
            $people[$person['id']] = $person['name'];
        }
        $msgCount = $imap->numMsg();
        if ($msgCount instanceof PEAR_Error) {
            $page->addBodyContent("<p class='error'>No messages found: '" . $msgCount->getMessage() . "'.</p>");
        } elseif ($msgCount > 0) {

            // Get message headers.
            $headers = $imap->getSummary(1);
            if ($headers instanceof PEAR_Error) {
                die('Failed to parse headers of message: ' . $headers->message);
            }
            $headers = $headers[0];
            $headers['FROM'] = (isset($headers['FROM'][0]['RFC822_EMAIL'])) ? $headers['FROM'][0]['RFC822_EMAIL'] : "";
            $headers['TO'] = (isset($headers['TO'][0]['RFC822_EMAIL'])) ? $headers['TO'][0]['RFC822_EMAIL'] : "";

            // Set defaults.
            $from_id = 0;
            $to_id = MAIN_USER_ID;
            $subject = $headers['SUBJECT'];
            if (empty($subject) || $subject == 'NIL') {
                $subject = '[No Subject]';
            } elseif (mb_check_encoding($subject)) {
                $subject = mb_decode_mimeheader($subject);
            }
            $editform_defaults = array(
                'date_and_time' => date('Y-m-d H:i:s', strtotime($headers['DATE'])),
                'subject' => $subject,
                'to_id' => $to_id,
                'from_id' => $from_id
            );

            // Determine correspondents
            $email_address_pattern = "/[a-zA-Z0-9\.\-_]*@[a-zA-Z0-9\.\-_]*/i";
            preg_match($email_address_pattern, $headers['FROM'], $from_email_address);
            preg_match($email_address_pattern, $headers['TO'], $to_email_address);
            if (isset($from_email_address[0])) {
                $from_email_address = '%' . $from_email_address[0] . '%';
                $from_person = $db->prepare("SELECT * FROM people WHERE email_address LIKE :email_address");
                $from_person->bindParam(':email_address', $from_email_address);
                $from_person->execute();
                if ($from_person = $from_person->fetch()) {
                    $from_id = $from_person['id'];
                    $editform_defaults['from_id'] = $from_id;
                }
            }
            if (isset($to_email_address[0])) {
                $to_email_address = '%' . $to_email_address[0] . '%';
                $to_person = $db->prepare("SELECT * FROM people WHERE email_address LIKE :email_address");
                $to_person->bindParam(':email_address', $to_email_address);
                $to_person->execute();
                if ($to_person = $to_person->fetch()) {
                    $to_id = $to_person['id'];
                    $editform_defaults['to_id'] = $to_id;
                }
            }


            // Get message body.
            function getMsgBody($structure)
            {
                global $imap;
                $message_body = "";
                if (isset($structure->subParts)) {
                    foreach ($structure->subParts as $pid => $part) {
                        if ($part->type == 'TEXT' && $part->subType == 'PLAIN') {
                            if ($part->encoding == 'QUOTED-PRINTABLE') {
                                $message_body .= quoted_printable_decode($imap->getBodyPart(1, $part->partID));
                            } elseif ($part->encoding == 'BASE64') {
                                $message_body .= base64_decode($imap->getBodyPart(1, $part->partID));
                            } else {
                                $message_body .= $imap->getBodyPart(1, $part->partID);
                            }
                        } elseif (isset($part->subParts)) {
                            $message_body .= getMsgBody($part);
                        }
                    }
                } else { // If no parts, then must be plain.
                    if ($structure->type == 'TEXT' && $structure->subType == 'HTML') {
                        $message_body = ($structure->encoding == 'BASE64') ? base64_decode($imap->getBody(1)) : $imap->getBody(1);
                        $message_body = quoted_printable_decode($message_body);
                        $message_body = strip_tags($message_body);
                        $message_body = str_replace("&nbsp;", " ", $message_body);
                        $message_body = html_entity_decode($message_body, ENT_QUOTES, $structure->parameters['CHARSET']);
                    } else {
                        if ($structure->encoding == 'BASE64') {
                            $message_body = base64_decode($imap->getBody(1));
                        } else {
                            $message_body = utf8_encode(quoted_printable_decode($imap->getBody(1)));
                        }
                    }
                }
                return trim($message_body);
            }

            $editform_defaults['message_body'] = getMsgBody($imap->getStructure(1));

            // Get form
            $num_msgs = $imap->numMsg();
            ob_start();
            require 'views/inbox_form.php';
            $person_form = ob_get_clean();
            $page->addBodyContent("<div class='span-24 last'>$person_form</div>");
        } else {
            $page->addBodyContent("<p class='success message'>No messages to accession.</p>");
        }


        /*******************************************************************************
         * Clean up and whatnot.
         ******************************************************************************/
        $imap->disconnect();
    }

    public function save(Request $request, Response $response, $args)
    {
        if (isset($_POST['save'])) {
            $save = $db->prepare('INSERT INTO emails SET date_and_time=:date_and_time, subject=:subject, from_id=:from_id, to_id=:to_id, message_body=:message_body');
            $save->bindParam(':date_and_time', $_POST['date_and_time']);
            $save->bindParam(':subject', $_POST['subject']);
            $save->bindParam(':from_id', $_POST['from_id']);
            $save->bindParam(':to_id', $_POST['to_id']);
            $save->bindParam(':message_body', $_POST['message_body']);
            $save->execute();
        }
        if ((isset($_POST['save']) && $_POST['save'] == 'Archive + Delete') || isset($_POST['delete'])) {
            $imap->deleteMsg($message_id);
            $imap->expunge();
            header("Location:" . $_SERVER['SCRIPT_URI']);
            exit();

        } elseif (isset($_POST['save']) && $_POST['save'] == 'Archive Only') {
            header("Location:" . $_SERVER['SCRIPT_URI']);
            exit();
        }

    }
}