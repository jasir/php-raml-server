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

require_once("Library/Route/Processor.php");

// set response headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');


$version = explode("/", ltrim($_SERVER['REQUEST_URI'], "/"))[0];


// read the RAML
$parser = new \Raml\Parser();
$apiDef = $parser->parseFromString("raml/" . $version . "/" . "example.raml", "/");

$app = new \Slim\Slim();

// This is where a persistence layer ACL check would happen on authentication-related HTTP request items
$authenticate = function ($app) {
    return function () use ($app) {
        if (false) {
            $app->halt(403, "Invalid security context");
        }
    };
};

foreach ($apiDef->getResourcesAsUri()->getRoutes() as $route) {

    $type = strtolower($route['method']->getType());
    $app->$type("/" . $apiDef->getVersion() . $route['path'], $authenticate($app), function () use ($app, $route) {
        $routeProcessor = new Processor($app->container, $route);
        $app->response->headers->set('Content-Type', 'application/json'); //default response type
    });

}

$app->run();