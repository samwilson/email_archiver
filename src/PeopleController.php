<?php

namespace Samwilson\EmailArchiver;

use Slim\Http\Request;
use Slim\Http\Response;

class PeopleController extends Controller
{
	
	

	/**
	 * Save person
	 */
	public function save()
	{
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
				$page->addBodyContent('<p class="message error">Unable to save. Error: <code>' . array_pop($save->errorInfo()) . '</code></p>');
			}
		}
	}

	public function create()
	{
		if (isset($_GET['person_text'])) {
			$email_address_pattern = "/[a-zA-Z0-9\.\-_]*@[a-zA-Z0-9\.\-_]*/i";
			preg_match("/^\"?(\S*)/i", $_GET['person_text'], $first_name);
			preg_match("/\s(\S*)/i", $_GET['person_text'], $surname);
			preg_match($email_address_pattern, $_GET['person_text'], $email_address);
		}

		$name = '';
		if (isset($first_name[1])) {
			$name = $first_name[1];
		}
		if (isset($surname[1])) {
			$name .= ' ' . $surname[1];
		}
		$person = array('name' => $name, 'email_address' => '', 'notes' => '');
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
			$header = 'Editing person #' . $_GET['id'];
		} else {
			$header = 'Enter new person data';
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function people(Request $request, Response $response, $args)
	{
		$people = $this->db->query("SELECT * FROM people ORDER BY name ASC")->fetchAll();
		$viewData = [
			'people' => $people,
		];
		return $this->view->render($response, 'people.html.twig', $viewData);
	}

}
