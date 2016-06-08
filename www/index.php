<?php
define('APPLICATION_PATH', __DIR__ . "/..");

require APPLICATION_PATH . '/vendor/autoload.php';

\Tracy\Debugger::enable();

//RobotLoader

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(APPLICATION_PATH . '/src');
$loader->addDirectory(APPLICATION_PATH . '/controllers');
$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage(APPLICATION_PATH . '/temp'));
$loader->register();

use Slim\Slim;
use Symfony\Component\Yaml\Yaml;

// Load configs and add to the app container
$app = new Slim([
    'mode' => 'production',
]);

// Only invoked if mode is "production"
$app->configureMode('production', function () use ($app) {
    $app->config(array(
        'log.enable' => true,
        'debug' => true
    ));
});

$configs = Yaml::parse(file_get_contents("../configs/configs.yml"));
$app->container->set('configs', $configs);

// parse version from request uri
$version = explode("/", ltrim($_SERVER['REQUEST_URI'], "/"))[0];

if (!$version) {
    header('Content-Type: text/markdown');
    echo file_get_contents("../README.md");
    die();
}

// parse configured RAML and add api definition to app container

$ramlDir = __DIR__ . "/raml/{$version}";
$ramlPath = "/{$configs['api_name']}.raml";
$source = file_get_contents($ramlDir . '/' . $ramlPath);

$parser = new \Raml\Parser();
$apiDef = $parser->parseFromString($source, $ramlDir);


$app->container->set('apiDef', $apiDef);

// This is where a persistence layer ACL check would happen on authentication-related HTTP request items
$authenticate = function (Slim $app) {
    return function () use ($app) {
        if (false) {
            $app->halt(403, "Invalid security context");
        }
    };
};

// Loop through the routes and register the API endpoints with the app

foreach ($apiDef->getResourcesAsUri()->getRoutes() as $route) {

    $httpMethod = strtolower($route['method']->getType());

    //get,post,...
    $app->$httpMethod(

        //route path
        '/' . $apiDef->getVersion() . $route['path'],

        //authenticate middleware
        $authenticate($app),

        //last middleware
        function () use ($app, $route) {

            // Process the route
            $routeProcessor = new Processor($app->container, $route);
            $routeProcessor->process();

            // API definitions are assumed to have this Content-Type for all content returned
            $app->response->headers->set('Content-Type', 'application/json');
        }
    );

}

$app->run();