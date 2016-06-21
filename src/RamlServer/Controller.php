<?php

/**
 * Base class for handling HTTP requests method handling
 *
 */
namespace RamlServer;

use Slim\Helper\Set;

abstract class Controller
{

    /**
     * Array of application configs from configs/configs.yml
     * @var array
     */
    protected $configs;
    /**
     * The parsed RAML definition for the route that we are processing
     * @var array
     */
    protected $route;
    /**
     * The request object
     * @var \Slim\Http\Request
     */
    protected $request;
    /**
     * The response object
     * @var \Slim\Http\Response
     */
    protected $response;


    /**
     * @param Set $appContainer
     * @param array $route
     * @internal param $ Slim\Helper\Set
     */
    public function __construct(Set $appContainer, array $route)
    {
        $this->route = $route;
        $this->configs = $appContainer->get('configs');
        $this->request = $appContainer->get('request');
        $this->response = $appContainer->get('response');
    }

}
