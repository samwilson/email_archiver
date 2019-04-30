<?php

namespace Samwilson\EmailArchiver;

use PhpImap\Mailbox;
use Slim\Http\Request;
use Slim\Http\Response;

class InboxController extends Controller
{

    public function inbox(Request $request, Response $response, $args)
    {
        $mailbox = $this->getMailbox();

        // Give up if there's no messages.
        $messageCount = $mailbox->countMails();
        if ($messageCount === 0) {
            return $this->renderView($response, 'base.html.twig', [
                'flash' => 'No messages found',
            ]);
        }

        // Get list of people.
        $ppl = $this->db->query("SELECT id, name FROM people ORDER BY name ASC");
        $people = [0 => 'n. i. d.'];
        foreach ($ppl as $person) {
            $people[$person['id']] = $person['name'];
        }

        // Get message.
        $mailId = 1;
        $uid = $mailbox->imap('uid', [ $mailId ]);
        $mail = $mailbox->getMail($uid, false);

        // Subject.
        $subject = $mail->subject;
        if (empty($subject) || $subject == 'NIL') {
            $subject = '[No Subject]';
        } elseif (mb_check_encoding($subject)) {
            $subject = mb_decode_mimeheader($subject);
        }

        // Sender and recipient.
        $emailStmt = $this->db->prepare("SELECT id FROM people WHERE email_address = :email_address");

        // Sender.
        $fromId = 0;
        $emailStmt->bindParam(':email_address', $mail->fromAddress);
        $emailStmt->execute();
        if ($fromPersonId = $emailStmt->fetchColumn()) {
            $fromId = $fromPersonId;
        }

        // Recipient.
        $toId = MAIN_USER_ID;
        $toAddresses = array_keys($mail->to);
        $toAddress = array_shift($toAddresses);
        $emailStmt->bindParam(':email_address', $toAddress);
        $emailStmt->execute();
        if ($toPersonId = $emailStmt->fetchColumn()) {
            $toId = $toPersonId;
        }

        // Return the view template.
        return $this->renderView($response, 'inbox.html.twig', [
            'message_count' => $messageCount,
            'people' => $people,
            'subject' => $subject,
            'date_and_time' => date('Y-m-d H:i:s', strtotime($mail->date)),
            'to_id' => $toId,
            'to_string' => $mail->toString,
            'from_id' => $fromId,
            'from_string' => $mail->fromName . '<' . $mail->fromAddress . '>',
            'message_body' => trim($mail->textPlain),
            'message_uid' => $mail->id,
        ]);
    }

    public function save(Request $request, Response $response, $args)
    {
        if ($request->getParam('save')) {
            // Save the email.
            $sql = 'INSERT INTO `emails` SET'
                .' `date_and_time` = :date_and_time,'
                .' `subject`       = :subject,'
                .' `from_id`       = :from_id,'
                .' `to_id`         = :to_id,'
                .' `message_body`  = :message_body';
            $save = $this->db->prepare($sql);
            $save->bindParam(':date_and_time', $request->getParam('date_and_time'));
            $save->bindParam(':subject', $request->getParam('subject'));
            $save->bindParam(':from_id', $request->getParam('from_id'));
            $save->bindParam(':to_id', $request->getParam('to_id'));
            $save->bindParam(':message_body', $request->getParam('message_body'));
            $save->execute();
        }
        if (($request->getParam('save') && $request->getParam('save') == 'Archive + Delete')
            || $request->getParam('delete')
        ) {
            // Delete the email.
            $mailbox = $this->getMailbox();
            $mailbox->deleteMail($request->getParam('message_uid'));
            $mailbox->expungeDeletedMails();
        }
        // Return to inbox.
        return $response->withRedirect($this->router->urlFor('inbox'));
    }

    /**
     * @return Mailbox
     */
    protected function getMailbox()
    {
        $imapSettings = $this->container->get('settings')->get('mailServer');
        $mailbox = new Mailbox($imapSettings['imap_path'], $imapSettings['username'], $imapSettings['password']);
        return $mailbox;
    }
}
