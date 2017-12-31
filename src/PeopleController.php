<?php

namespace Samwilson\EmailArchiver;

use Slim\Http\Request;
use Slim\Http\Response;

class PeopleController extends Controller
{

	/**
	 * Save person
	 */
	public function save(Request $request, Response $response, $args)
	{
		if (!$request->getParam('name')) {
			return $response->withRedirect($this->router->pathFor('person_new'));
		}
		$id = $request->getParam('id', false);
		if ($id) {
			$save = $this->db->prepare('UPDATE people SET name=:name, email_address=:email_address, notes=:notes WHERE id=:id');
			$save->bindParam(':id', $id);
		} else {
			$save = $this->db->prepare('INSERT INTO people SET name=:name, email_address=:email_address, notes=:notes');
		}
		$save->bindParam(':name', $request->getParam('name'));
		$save->bindParam(':email_address', $request->getParam('email_address'));
		$save->bindParam(':notes', $request->getParam('notes'));
		if (!$save->execute()) {
			throw new \Exception('Unable to save. Error: ' . array_pop($save->errorInfo()));
		}
		return $response->withRedirect($this->router->pathFor('people'));
	}

	public function edit(Request $request, Response $response, $args)
	{
		$personText = $request->getQueryParam('person_text');
		if ($personText) {
			preg_match("/^\"?(\S*)/i", $personText, $firstName);
			preg_match("/\s(\S*)/i", $personText, $surname);
			$emailSddressPattern = "/[a-zA-Z0-9\.\-_]*@[a-zA-Z0-9\.\-_]*/i";
			preg_match($emailSddressPattern, $personText, $emailAddress);
		}

		$name = '';
		if (isset($firstName[1])) {
			$name = $firstName[1];
		}
		if (isset($surname[1])) {
			$name .= ' ' . $surname[1];
		}
		$person = ['name' => $name, 'email_address' => '', 'notes' => ''];
		if (isset($emailAddress[0])) {
			$person['email_address'] = $emailAddress[0];
		} else {
			$person['email_address'] = '';
		}

		$id = (int)$request->getAttribute('id');
		if ($id) {
			$person = $this->db->prepare("SELECT id, name, email_address, notes FROM people WHERE id=:id LIMIT 1");
			$person->bindParam(':id', $id);
			$person->execute();
			$person = $person->fetch();
		}
		$viewData = [
			'person' => $person,
		];
		return $this->renderView($response, 'person_form.html.twig', $viewData);
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
		return $this->renderView($response, 'people.html.twig', $viewData);
	}

}
