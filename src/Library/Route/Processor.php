<?php

require_once("Library/Route/Methods.php");
use Slim\Helper\Set;

class Processor
{

    private $appContainer;
    private $route;
    private $response;

    public function __construct(Set $appContainer, array $route)
    {
        $this->appContainer = $appContainer;
        $this->route = $route;

        // Get the method name
        $pathinfo = pathinfo ($this->route['path']);
        //trim the leading slash
        $dirname = ltrim ($pathinfo['dirname'], '/');
        //replace slashes with underscores and append basename
        $method = $dirname ? str_replace("/", "_", $dirname) . "_" . $pathinfo['basename'] : $pathinfo['basename'];

        
        $methods = new Methods($appContainer, $route);
        // check if example response is requested
        if ($this->appContainer['request']->headers->get('Example-Response-Body')) {
            $this->response = $this->getExampleResponseBody($this->appContainer['request']->headers->get('Example-Response-Code'));
            $appContainer['response']->setStatus($this->appContainer['request']->headers->get('Example-Response-Code'));
        } else {
            $this->response = $methods->$method();
        }
    }

    private function getExampleResponseBody($responseCode = 200)
    {
        $responses = $this->route['method']->getResponses();
        try {
            return $responses[$responseCode]->getBodyByType('application/json')->getExample();
        } catch (Exception $e) {
            return null;
        }
    }

    public function getResponse()
    {
        return $this->response;
    }

}
