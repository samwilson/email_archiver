<?php

namespace Samwilson\EmailArchiver;

use Slim\Http\Request;
use Slim\Http\Response;
use SlimSession\Helper as SessionHelper;

class UserController extends Controller
{

    protected $requireLogin = false;

    public function login(Request $request, Response $response, $args)
    {
        if ($this->session->exists('logged_in')) {
            return $response->withRedirect($this->router->pathFor('home'));
        }
        return $this->view->render(
            $response,
            'login.html.twig',
            ['flash' => $this->getFlash()]
        );
    }

    public function loginPost(Request $request, Response $response, $args)
    {
        if ($this->session->exists('logged_in')) {
            return $response->withRedirect($this->router->pathFor('home'));
        }
        $appPass = $this->settings->get('appPass');
        $pass = $request->getParam('password');
        if (!password_verify($pass, $appPass)) {
            $this->setFlash('Authentication failure');
            return $response->withRedirect($this->router->pathFor('login'));
        }
        $this->session->set('logged_in', true);
        SessionHelper::id(true);
        return $response->withRedirect($this->router->pathFor('home'));
    }

    public function logout(Request $request, Response $response, $args)
    {
        $this->session->delete('logged_in');
        SessionHelper::id(true);
        $this->setFlash('Logged out');
        return $response->withRedirect($this->router->pathFor('login'));
    }
}
