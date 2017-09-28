<?php

namespace Samwilson\EmailArchiver;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

abstract class Controller {
    
    /** @var ContainerInterface */
    protected $container;

    /** @var Twig */
    protected $view;
    
    /** @var Connection */
    protected $db;

    public function __construct(ContainerInterface $container)
    {
        $this->view = $container->get('view');
        $this->container = $container;
        $this->db = $container->db;
    }

}