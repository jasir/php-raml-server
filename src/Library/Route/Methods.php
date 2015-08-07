<?php

/**
 * Base class for handling HTTP requests method handling
 *
 */
use Slim\Helper\Set;

class MethodsBase
{

    /**
     * Array of application configs from configs/configs.yml
     * @var array
     */
    private $configs;
    /**
     * The parsed RAML definition for the route that we are processing
     * @var array
     */
    private $route;
     /**
     * The request object
     * @var \Slim\Http\Request
     */
    private $request;
    /**
     * The response object
     * @var \Slim\Http\Response
     */
    private $response;

    /**
     * @param Slim\Helper\Set
     * @param array
     */
    public function __construct(Set $appContainer, array $route)
    {
        $this->route = $route;
        $this->configs = $appContainer->get("configs");
        $this->request = $appContainer->get("request");
        $this->response = $appContainer->get("response");
    }

    /**
     * /hello?test={test}
     * @return object
     */
    public function getHello ()
    {
        $response = new stdClass();
        $response->message = "hello";
        $response->test = $this->request->params('test');
        return $response;
    }



}
