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

use Symfony\Component\Yaml\Yaml;
require_once("Library/Route/Processor.php");
require_once('Library/Route/Methods.php');

// set response headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

// Load configs and add to the app container
$app = new \Slim\Slim();
$configs = Yaml::parse(file_get_contents("../configs/configs.yml"));
$app->container->set('configs', $configs);

// parse version from request uri
$version = explode("/", ltrim($_SERVER['REQUEST_URI'], "/"))[0];

// parse configured RAML and add api definition to app container
$parser = new \Raml\Parser();
// echo "raml/" . $version . "/" . $configs['api_name'] . ".raml"; die();
$apiDef = $parser->parseFromString("raml/" . $version . "/" . $configs['api_name'] . ".raml", "/");
$app->container->set('apiDef', $apiDef);

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
        // API definitions are assumed to have this Content-Type for all content returned
        $app->response->headers->set('Content-Type', 'application/json');
    });

}

$app->run();