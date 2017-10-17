<?php

namespace Samwilson\EmailArchiver;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Slim\Router;
use Slim\Views\Twig;

abstract class Controller {

    /** @var ContainerInterface */
    protected $container;

    /** @var Twig */
    protected $view;

    /** @var Connection */
    protected $db;

    /** @var Router */
    protected $router;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->view = $container->get('view');
        $this->db = $container->get('db');
        $this->router = $container->get('router');
    }

}