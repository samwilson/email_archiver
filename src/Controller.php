<?php

namespace Samwilson\EmailArchiver;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Slim\Collection;
use Slim\Http\Response;
use Slim\Router;
use Slim\Views\Twig;
use SlimSession\Helper;

abstract class Controller
{

    /** @var ContainerInterface */
    protected $container;

    /** @var Twig */
    protected $view;

    /** @var Connection */
    protected $db;

    /** @var Router */
    protected $router;

    /** @var Collection */
    protected $settings;

    /** @var Helper */
    protected $session;

    protected $requireLogin = true;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = $container->get('view');
        $this->db = $container->get('db');
        $this->router = $container->get('router');
        //$this->session = $container->get('session');
        $this->settings = $container->get('settings');
        if ($this->requireLogin && !isset($_SESSION['logged_in'])) {
            header('Location: '.$this->router->pathFor('login'));
            exit();
        }
    }

    protected function renderView(Response $response, $view, $data)
    {
        return $this->view->render(
            $response,
            $view,
            array_merge($data, [
                'logged_in' => isset($_SESSION['logged_in']),
            ])
        );
    }

    protected function setFlash($message)
    {
        $_SESSION['flash'] = $message;
    }

    protected function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return false;
    }
}
