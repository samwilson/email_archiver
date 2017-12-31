<?php

namespace Samwilson\EmailArchiver;

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

	public function home(Request $request, Response $response, $args)
	{
		$with = $request->getParam('with', false);
		$mostRecentYear = $request->getParam('year', false);

		$mainUser = $this->db->query('SELECT * FROM people WHERE id = ' . MAIN_USER_ID);
		$mainUser = $mainUser->fetch();
		if (!$mainUser) {
			throw new Exception("Main user not found");
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
		if (count($years) == 0) {
			//echo "<p>Nothing found.</p>";
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
		$viewData['email_count'] = $this->db->query('SELECT COUNT(*) FROM emails LIMIT 1')->fetchColumn();


		/*******************************************************************************
		 * list emails
		 ******************************************************************************/
		$emails = [];
		if ($mostRecentYear || $with) {
			$sql = "SELECT * FROM emails WHERE YEAR(date_and_time) = :year ";
			$params = [':year' => $mostRecentYear];
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
		if ($with) {
			$to = $this->getPerson($with);
			
			// Subject
//            if (stristr($last_subject,'re') === FALSE) {
//                $subject = 'Re: '.$last_subject;
//            } else {
//                $subject = $last_subject;
//            }
		}

		/*******************************************************************************
		 * who still to reply to
		 ******************************************************************************/

		$peopleInfo = [];
		foreach ($people as $pid => $name) {
			if ($pid == MAIN_USER_ID) {
				continue;
			}
			// Get information about the last email from this person.
			$sql = "SELECT from_id, to_id, YEAR(date_and_time) AS year FROM emails
			WHERE to_id = :pid OR from_id = :pid ORDER BY date_and_time DESC LIMIT 1";
			$unanswered = $this->db->prepare($sql);
			$unanswered->execute([':pid' => $pid]);
			$unanswered = $unanswered->fetch();

			if ($unanswered) { // If there is any last email.
				$from_id = $unanswered['from_id'];
				$to_id = $unanswered['to_id'];
				$mostRecentYear = $unanswered['year'];
				// If the last email was incoming and not from the main user.
				if ($from_id != MAIN_USER_ID) {
					$cssClass = 'highlight';
					//$with = $from_id;
					//$page->addBodyContent("<li><strong><a style='color:red' href='?with={$from_id}&year={$year}#reply-form'>$name</a></strong></li>");
				} else {
					//$with = $to_id;
					$cssClass = '';
					//$page->addBodyContent("<li><a href='?with={$to_id}&year={$year}#reply-form'>$name</a></li>");
					//} elseif () {
					//$page->addBodyContent("<li><a href='?with=$pid&year=".date('Y')."#reply-form'>$name</a></li>");
				}
				$from_id = null;
				$to_id = null;
				//$year = null;
			} else { // There was no last email.
				$mostRecentYear = date('Y');
				//$with = $pid;
				$cssClass = '';
			}
			$peopleInfo[] = [
				'id' => $pid,
				'name' => $name,
				'css_class' => $cssClass,
				'most_recent_year' => $mostRecentYear,
			];
		}

		return $this->renderView(
			$response,
			'emails.html.twig',
			[
				'with' => $with,
				'to' => $to,
				'year' => $mostRecentYear,
				'emails' => $emails,
				'people' => $peopleInfo,
			]
		);
	}

	/**
	 * The POST endpoint for sending email.
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 */
	public function send(Request $request, Response $response, $args)
	{
		$body = $request->getParam('message_body');
		$lastBody = $request->getParam('last_body');
		$to = $this->getPerson($request->getParam('with'));
		$mainUser = $this->getPerson(MAIN_USER_ID);
		if (!empty($lastBody)) {
			$body .= "\n\n
-------- Previous message --------
-- Date: " . $request->getParam('last_date') . "
-- From: " . $to['name'] . " <" . $to['email_address'] . ">
--   To: " . $mainUser['name'] . " <" . $mainUser['email_address'] . ">
--------

" . wordwrap($_POST['last_body'], 78) . "

-------- End of previous message --------
";
		}

		// Send the message.
		$server = $this->settings->get('mailServer');
		$message = Swift_Message::newInstance($request->getParam('subject'), $body)
			->setFrom(array($mainUser['email_address'] => $mainUser['name']))
			->setTo(array($to['email_address'] => $to['name']));
		$transport = Swift_SmtpTransport::newInstance($server['smtp_server'], $server['smtp_port'])
			->setUsername($server['username'] . $server['suffix'])
			->setPassword($server['password']);
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
			exit(1);
		}

		header("Location:" . $_SERVER['PHP_SELF'] . "?with=$with&year=$year#reply-form");
	}

	protected function getEmails($with, $year)
	{
		$sql = "SELECT * FROM emails WHERE YEAR(date_and_time)=:year ";
		$params = [':year' => $year];
		if ($with) {
			$sql .= "AND (to_id = :with OR from_id = :with) ";
			$params[':with'] = $with;
		}
		$sql .= "ORDER BY date_and_time ASC";

		$emails = $this->db->prepare($sql);
		$emails->execute($params);
		$emails = $emails->fetchAll();
		$last_subject = '';
		$last_body = '';
		$last_date = '';
		$last_from_id = '';

		if (count($emails) > 0) {
			foreach ($emails as $count => $email) {
				$email_class = ($email['from_id'] == MAIN_USER_ID) ? 'from-me' : '';
				$page->addBodyContent("<div class='email $email_class'><p>");
				if ($count == count($emails) - 1) {
					$page->addBodyContent("<a name='reply-form'></a>");
				}
				$page->addBodyContent("
                <span class='from $email_class'>" . $people[$email['from_id']] . "</span>
            ");
				if (!$with) {
					$page->addBodyContent(" (to " . $people[$email['to_id']] . ") ");
				}
				$page->addBodyContent(
					date('l, F jS, g:iA', strtotime($email['date_and_time'])) . "
                    &nbsp;&nbsp;
                    <strong>" . $email['subject'] . "</strong> &nbsp;&nbsp;
                    <!--span class='small quiet'>
                      <a href='?table_name=emails&edit&id=" . $email['id'] . "'>[e]</a>
                      <del><a href='?table_name=emails&delete&id=" . $email['id'] . "'>[d]</a></del>
                    </span-->
                    </p><pre>" . trim(wordwrap(htmlentities($email['message_body']), 78)) . "</pre></div>");
				$last_subject = $email['subject'];
				$last_body = $email['message_body'];
				$last_date = $email['date_and_time'];
				$last_from_id = $email['from_id'];
			}
			$page->addBodyContent("<p class='centre'>Showing " . count($emails) . " emails.</p>");
		}
		return $email;
	}
}