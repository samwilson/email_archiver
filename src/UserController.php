<?php

namespace Samwilson\EmailArchiver;

use Slim\Http\Request;
use Slim\Http\Response;

class UserController extends Controller {

	protected $requireLogin = false;

	public function login(Request $request, Response $response, $args)
	{
		if (isset($_SESSION['logged_in'])) {
			return $response->withRedirect($this->router->pathFor('home'));
		}
		return $this->view->render(
			$response,
			'login.html.twig',
			[
				'flash' => $this->getFlash(),
			]
		);
	}

	public function loginPost(Request $request, Response $response, $args) {
		if (isset($_SESSION['logged_in'])) {
			return $response->withRedirect($this->router->pathFor('home'));
		}
		$appPass = $this->settings->get('appPass');
		$pass = $request->getParam('password');
		if (!password_verify ($pass, $appPass)) {
			$this->setFlash('Authentication failure');
			return $response->withRedirect($this->router->pathFor('login'));
		}
		$_SESSION['logged_in'] = true;
		return $response->withRedirect($this->router->pathFor('home'));
	}

	public function logout(Request $request, Response $response, $args)
	{
		unset($_SESSION['logged_in']);
		session_regenerate_id();
		$this->setFlash('Logged out');
		return $response->withRedirect($this->router->pathFor('login'));
	}
}
