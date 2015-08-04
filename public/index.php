<?php
require '../vendor/autoload.php';

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
define("APPLICATION_PATH", __DIR__ . "/..");
date_default_timezone_set('America/New_York');

// Ensure src/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    APPLICATION_PATH ,
    APPLICATION_PATH . '/src',
    get_include_path(),
)));

// set response headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

// read the RAML
$parser = new \Raml\Parser();
$apiDef = $parser->parseFromString("../raml/example.raml", "/");

$app = new \Slim\Slim();

/**
 * Authentication should be run as middleware before each route
 */
$authenticate = function ($app) {
    return function () use ($app) {
        if ( 
            !$app->request->headers->get('Vendor-Id')
            || !$app->request->headers->get('User-Id')
        ) {
            $app->halt(403, "Invalid security context");
        }
    };
};

foreach ($apiDef->getResourcesAsUri()->getRoutes() as $route) {

    // var_dump(strtolower($route['method']->getType())); die();

    $type = strtolower($route['method']->getType());
    $app->$type("/" . $apiDef->getVersion() . $route['path'], $authenticate($app), function () use ($app, $route) {
        
    });

    // $class = new ReflectionClass('Route');
    // $methods = $class->getMethods();
    // var_dump($methods);
}

$app->run();