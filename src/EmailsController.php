<?php

namespace Samwilson\EmailArchiver;

use DateTime;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class EmailsController extends Controller
{

    protected function getPerson($id)
    {
        $sql = 'SELECT id, name, email_address FROM people WHERE id = :id';
        $person = $this->db->prepare($sql);
        $person->execute([':id' => $id]);
        $person = $person->fetch();
        return $person;
    }

    protected function getPeople()
    {
        $peopleData = $this->db->query('SELECT `id`, `name` FROM `people` ORDER BY `name` ASC')
            ->fetchAll();
        $people = [];
        foreach ($peopleData as $person) {
            $people[$person['id']] = $person['name'];
        }
        return $people;
    }

    public function home(Request $request, Response $response, $args)
    {
        $with = $request->getParam('with', false);
        $year = $request->getParam('year', false);

        $mainUser = $this->getPerson(MAIN_USER_ID);
        if (!$mainUser) {
            throw new Exception("Main user not found");
        }

        /*******************************************************************************
         * Get years.
         ******************************************************************************/
        $years = [];
        $res = $this->db->query("SELECT YEAR(`date_and_time`) AS `year` FROM `emails` GROUP BY `year`");
        $res->execute();
        foreach ($res->fetchAll() as $y) {
            $years[$y['year']] = $y['year'];
        }
        if (count($years) == 0) {
            //echo "<p>Nothing found.</p>";
        }

        /*******************************************************************************
         * Get people
         ******************************************************************************/
        $people = $this->getPeople();

        /*******************************************************************************
         * list emails
         ******************************************************************************/
        $emails = [];
        if ($year || $with) {
            $sql = "SELECT * FROM emails WHERE YEAR(date_and_time) = :year ";
            $params = [':year' => $year];
            if ($with) {
                $sql .= "AND (to_id = :with OR from_id = :with) ";
                $params[':with'] = $with;
            }
            $sql .= "ORDER BY date_and_time ASC";
            $emailsQuery = $this->db->prepare($sql);
            $emailsQuery->execute($params);
            $emails = $emailsQuery->fetchAll();
        }

        /*******************************************************************************
         * reply form
         ******************************************************************************/
        $to = null;
        $subject = '';
        $lastBody = '';
        if ($with) {
            $to = $this->getPerson($with);

            // Last subject and body.
            if (count($emails) > 0) {
                $lastEmail = $emails[count($emails) - 1];
                $lastSubject = $lastEmail['subject'];
                if (stristr($lastSubject, 're') === false) {
                    $subject = 'Re: ' . $lastSubject;
                } else {
                    $subject = $lastSubject;
                }
                if ((int)$lastEmail['from_id'] !== MAIN_USER_ID) {
                    $lastBody = $lastEmail['message_body'];
                }
            }
        }

        /*******************************************************************************
         * who still to reply to
         ******************************************************************************/
        $peopleInfo = [];
        foreach ($people as $pid => $name) {
            // Get information about the last email from this person.
            $sql = 'SELECT from_id, to_id, YEAR(date_and_time) AS year'
                .' FROM emails'
                .' WHERE to_id = :pid OR from_id = :pid'
                .' ORDER BY date_and_time DESC'
                .' LIMIT 1';
            $unanswered = $this->db->prepare($sql);
            $unanswered->execute([':pid' => $pid]);
            $unanswered = $unanswered->fetch();

            if ($unanswered) { // If there is any last email.
                $mostRecentYear = $unanswered['year'];
                $cssClass = (int)$unanswered['from_id'] !== MAIN_USER_ID ? 'highlight' : '';
            } else { // There was no last email.
                $mostRecentYear = date('Y');
                $cssClass = '';
            }
            $peopleInfo[$pid] = [
                'id' => $pid,
                'name' => $name,
                'css_class' => $cssClass,
                'most_recent_year' => $mostRecentYear,
            ];
        }

        $emailCount = $this->db->query('SELECT COUNT(*) FROM emails LIMIT 1')->fetchColumn();

        return $this->renderView(
            $response,
            'emails.html.twig',
            [
                'main_user_id' => MAIN_USER_ID,
                'with' => $with,
                'to' => $to,
                'years' => $years,
                'year' => $year,
                'emails' => $emails,
                'people' => $peopleInfo,
                'email_count' => $emailCount,
                'subject' => $subject,
                'last_body' => $lastBody,
            ]
        );
    }

    /**
     * The POST endpoint for sending email.
     * @param Request $request
     * @param Response $response
     * @param string[] $args
     * @return Response
     */
    public function send(Request $request, Response $response, $args)
    {
        $to = $this->getPerson($request->getParam('with'));
        $mainUser = $this->getPerson(MAIN_USER_ID);
        $body = $request->getParam('message_body');

        // Get the most recent email to or from this person and append it to the message being sent.
        $sql = "SELECT * FROM emails WHERE to_id=:to OR from_id=:to ORDER BY date_and_time DESC LIMIT 1";
        $params = [':to' => $to['id']];
        $lastEmailQuery = $this->db->prepare($sql);
        $lastEmailQuery->execute($params);
        $lastEmail = $lastEmailQuery->fetch();
        if ($lastEmail) {
            $body .= "\n\n
---------- Previous message ----------
--    Sender: " . $to['name'] . " <" . $to['email_address'] . ">
-- Recipient: " . $mainUser['name'] . " <" . $mainUser['email_address'] . ">
--      Date: " . $lastEmail['date_and_time'] . "
--   Subject: " . $lastEmail['subject'] . "
--------------------------------------

" . wordwrap($lastEmail['message_body'], 78) . "

-------- End of previous message --------
";
        }

        // Send the message.
        $server = $this->settings->get('mailServer');
        $message = Swift_Message::newInstance($request->getParam('subject'), $body)
            ->setFrom(array($mainUser['email_address'] => $mainUser['name']))
            ->setTo(array($to['email_address'] => $to['name']));
        $transport = Swift_SmtpTransport::newInstance($server['smtp_server'], $server['smtp_port'])
            ->setUsername($server['username'])
            ->setPassword($server['password']);
        if ($server['smtp_encryption']) {
            $transport->setEncryption($server['smtp_encryption']);
        }
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
        $insert->bindParam(':to', $to['id']);
        $insert->bindParam(':date_and_time', date('Y-m-d H:i:s'));
        $insert->bindParam(':subject', $_POST['subject']);
        $insert->bindParam(':message_body', $_POST['message_body']);
        if (!$insert->execute()) {
            print_r($insert->errorInfo());
            exit(1);
        }

        $year = date('Y');
        $url = $this->router->urlFor('home') . '?with='.$to['id'].'&year='.$year.'#last-email';
        return $response->withRedirect($url);
    }

    public function edit(Request $request, Response $response, $args)
    {
        $sql = 'SELECT `id`, `from_id`, `to_id`, `date_and_time`, `subject`, `message_body`'
            .' FROM `emails`'
            .' WHERE `id` = :id LIMIT 1';
        $emailStmt = $this->db->prepare($sql);
        $emailStmt->bindValue('id', (int)$args['id']);
        $emailStmt->execute();
        $email = $emailStmt->fetch();
        return $this->renderView(
            $response,
            'email_form.html.twig',
            [
                'email' => $email,
                'people' => $this->getPeople(),
            ]
        );
    }

    public function save(Request $request, Response $response, $args)
    {
        $sql = 'UPDATE `emails` SET'
            .' `from_id` = :from_id, `to_id` = :to_id, `date_and_time` = :date_and_time,'
            .' `subject` = :subject, `message_body` = :message_body'
            .' WHERE `id` = :id';
        $save = $this->db->prepare($sql);
        $id = (int)$request->getParam('id');
        $date = $request->getParam('date_and_time');
        $fromId = $request->getParam('from_id');
        $toId = $request->getParam('to_id');
        $save->bindParam(':date_and_time', $date);
        $save->bindParam(':subject', $request->getParam('subject'));
        $save->bindParam(':from_id', $fromId);
        $save->bindParam(':to_id', $toId);
        $save->bindParam(':message_body', $request->getParam('message_body'));
        $save->bindParam(':id', $id);
        $save->execute();
        $year = (new DateTime($date))->format('Y');
        $with = $fromId == MAIN_USER_ID ? $toId : $fromId;
        return $response->withRedirect($this->router->urlFor('home').'?with='.$with.'&year='.$year.'#email-'.$id);
    }
}
