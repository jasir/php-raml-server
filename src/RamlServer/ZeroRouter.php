<?php

namespace RamlServer;

use InvalidArgumentException;
use Raml\Parser;
use Slim\Slim;
use Symfony\Component\Yaml\Yaml;


/**
 * Class ZeroRouter
 *
 * Provides very simple routing facility before normal router
 * will take place. It can be used as dispatcher among RamlServerRouter and
 * normal application router.
 *
 * Usage:
 * $uri = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'
 * $zeroRouter = new ZeroRouter($uri, 'http://test-server/api')
 *
 * @package RamlServer
 */
class ZeroRouter
{

    /** @var string */
    private $uri;

    /** @var string */
    private $apiUri;

    /** @var bool */
    private $isApi;

    /** @var string */
    private $apiName;

    /** @var string */
    private $version;

    /** @var array */
    private $options;


    /**
     * ZeroRouter constructor.
     * @param array $options
     * @param string $uri
     */
    public function __construct(array $options, $uri)
    {
        $this->options = $options;
        $this->uri = $uri;
        $this->apiUri = $this->getOption('server') . '/' . $this->getApiUriPart();
        $this->isApi = false;

        if (strpos($uri, $this->apiUri) !== 0) {
            return;
        }

        $part = substr($uri, strlen($this->apiUri) + 1);
        $parts = explode('/', $part);

        //at least api-name and version must be part of url
        if (count($parts) < 2 || empty($parts[0]) || empty($parts[1])) {
            return;
        }

        list($this->apiName, $this->version) = $parts;
        $this->isApi = true;

    }


    public function run()
    {

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

        // parse configured RAML and add api definition to app container


        $ramlIndexPath = $this->getApiIndexFile();

        $source = file_get_contents($ramlIndexPath);

        $parser = new Parser();
        $apiDef = $parser->parseFromString($source, $this->getApiDirectory());

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

        $apiStarts = $this->getApiUriPart() . '/' . $this->getApiName();

        foreach ($apiDef->getResourcesAsUri()->getRoutes() as $route) {


            $httpMethod = strtolower($route['method']->getType());

            //get,post,...
            $app->$httpMethod(

                //route path
                '/' . $apiStarts . '/' . $apiDef->getVersion() . $route['path'],

                //authenticate middleware
                $authenticate($app),

                //last middleware
                function () use ($app, $route) {

                    // Process the route
                    $routeProcessor = new Processor($this, $app->container, $route);
                    $routeProcessor->process();

                    // API definitions are assumed to have this Content-Type for all content returned
                    $app->response->headers->set('Content-Type', 'application/json');
                }
            );

        }

        $app->run();
    }


    /**
     * @return bool
     */
    public function isApiRoute()
    {
        return $this->isApi;
    }


    /**
     * <http://...server>/<apiUriPath>/<apiName>/users
     * @return string|null
     */
    public function getApiName()
    {
        return $this->apiName;
    }


    /**
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }


    /**
     * @see getApiName
     */
    public function getApiUriPart()
    {
        return $this->getOption('apiUriPart');
    }


    /**
     * @return string
     */
    protected function getApiDirectory()
    {
        return $this->getRamlRootDirectory() . '/' . $this->getApiName() . '/' . $this->getVersion();
    }


    /**
     * @return string
     */
    public function getApiIndexFile()
    {
        return
            $this->getApiDirectory() . '/index.raml';
    }


    /**
     * @return string
     */
    public function getRamlRootDirectory()
    {
        return $this->getOption('ramlDir');
    }


    /**
     * @return mixed|null
     */
    public function getControllerNamespace()
    {
        return $this->getOption('controllerNamespace');
    }


    /**
     * @param $optionName
     * @param null $default
     * @return mixed|null
     */
    public function getOption($optionName, $default = null) {
        if (array_key_exists($optionName, $this->options)) {
            return $this->options[$optionName];
        }
        if (func_num_args() === 1) {
            throw new InvalidArgumentException(
                "RamlServer: Invalid configuration, key `$optionName` is missing"
            );
        }
        return $default;
    }


}