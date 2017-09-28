<?php

require __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Samwilson\EmailArchiver\EmailsController;
use Samwilson\EmailArchiver\InboxController;
use Slim\App;
use Slim\Container;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

// Set up configuration.
$configFilename = __DIR__.'/config.php';
if (file_exists($configFilename)) {
    $config = (function() use ($configFilename) {
        require_once $configFilename;
        unset($configFilename);
        return get_defined_vars();
    })();
}
$configDefaults = [
    'varDir' => __DIR__ . '/var',
];
$config = array_merge((isset($config) ? $config : []), $configDefaults);

// Set up Slim application.
$app = new App(['settings' =>  $config]);
$container = $app->getContainer();

// Views.
$container['view'] = function (Container $container) {
    if ($container->get('settings')->get('displayErrorDetails')) {
        $twigOptions = [
            'debug' => true,
        ];
    } else {
        $twigOptions = [
            'cache' => $container['settings']['varDir'] . '/twig'
        ];
    }
    $view = new Twig(__DIR__.'/tpl', $twigOptions);
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new TwigExtension($container['router'], $basePath));
    return $view;
};

// Database.
$container['db'] = function (Container $container) {
    $config = new Configuration();
    //echo '<pre>';var_dump($container['settings']);exit();
    $connectionParams = array(
        'driver' => 'pdo_mysql',
        'url' => $container['settings']['dbDsn'],
        'port' => $container['settings']['dbPort'],
        'user' => $container['settings']['dbUser'],
        'password' => $container['settings']['dbPass'],
    );
    $conn = DriverManager::getConnection($connectionParams, $config);
    return $conn;
};

// Routes.
$app->get('/', EmailsController::class.':home')->setName('home');
$app->get('/inbox', InboxController::class.':inbox')->setName('inbox');

// Run the application.
$app->run();
