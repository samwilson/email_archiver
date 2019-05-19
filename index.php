<?php

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Samwilson\EmailArchiver\EmailsController;
use Samwilson\EmailArchiver\InboxController;
use Samwilson\EmailArchiver\LatexController;
use Samwilson\EmailArchiver\PeopleController;
use Samwilson\EmailArchiver\UserController;
use Slim\App;
use Slim\Container;
use Slim\Middleware\Session;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;
use SlimSession\Helper;

// Set up configuration.
$configFilename = __DIR__ . '/config.php';
if (file_exists($configFilename)) {
    $config = (function () use ($configFilename) {
        require_once $configFilename;
        foreach (['dbDsn', 'dbUser', 'dbPass'] as $reqVar) {
            if (!isset($$reqVar)) {
                echo "Please set $$reqVar in $configFilename";
                exit(1);
            }
        }
        unset($configFilename);
        return get_defined_vars();
    })();
}
$configDefaults = [
    'varDir' => __DIR__ . '/var',
];
$config = array_merge((isset($config) ? $config : []), $configDefaults);

// Set up Slim application.
$app = new App(['settings' => $config]);
$container = $app->getContainer();

// Views.
$container['view'] = function (Container $container) {
    if ($container->get('settings')->get('displayErrorDetails')) {
        $twigOptions = [
            'debug' => true,
            'cache' => false,
        ];
    } else {
        $twigOptions = [
            'debug' => false,
            'cache' => $container['settings']['varDir'] . '/twig'
        ];
    }
    $view = new Twig(__DIR__ . '/tpl', $twigOptions);
    $view->addExtension(new Twig_Extension_Debug());
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new TwigExtension($container['router'], $basePath));

    // Add filters.
    $view->getEnvironment()->addFilter(new Twig_SimpleFilter('wordwrap', function ($str, $width = 75) {
        return wordwrap($str, $width);
    }));

    return $view;
};

// Session.
$app->add(new Session([
    'name' => 'email_archiver',
    'autorefresh' => true,
    'lifetime' => '1 day'
]));
$container['session'] = function () {
    return new Helper;
};

// Database.
$container['db'] = function (Container $container) {
    $config = new Configuration();
    $connectionParams = [
        'driver' => 'pdo_mysql',
        'url' => $container['settings']['dbDsn'],
        'port' => $container['settings']['dbPort'],
        'user' => $container['settings']['dbUser'],
        'password' => $container['settings']['dbPass'],
    ];
    $conn = DriverManager::getConnection($connectionParams, $config);
    $conn->exec('SET NAMES utf8');
    return $conn;
};

// Routes.
$app->get('/', EmailsController::class . ':home')->setName('home');
$app->post('/send', EmailsController::class . ':send')->setName('send');
$app->get('/emails/{id}/edit', EmailsController::class . ':edit')->setName('email_edit');
$app->post('/emails/save', EmailsController::class . ':save')->setName('email_save');
$app->get('/{year}.tex', LatexController::class . ':home')->setName('latex');
$app->get('/inbox', InboxController::class . ':inbox')->setName('inbox');
$app->post('/inbox', InboxController::class . ':save')->setName('inbox_save');
$app->get('/people', PeopleController::class . ':people')->setName('people');
$app->get('/people/new', PeopleController::class . ':edit')->setName('person_new');
$app->get('/people/{id}/edit', PeopleController::class . ':edit')->setName('person_edit');
$app->get('/people/{id}/delete', PeopleController::class . ':delete')->setName('person_delete');
$app->post('/people/save', PeopleController::class . ':save')->setName('person_save');
$app->get('/login', UserController::class . ':login')->setName('login');
$app->post('/login', UserController::class . ':loginPost')->setName('login_post');
$app->get('/logout', UserController::class . ':logout')->setName('logout');

// Run the application.
$app->run();
