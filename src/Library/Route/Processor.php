<?php

// namespace Library\Route;
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


        $pathinfo = pathinfo ($this->route['path']);
        //trim the leading slash
        $dirname = ltrim ($pathinfo['dirname'], '/');
        //replace slashes with underscores and append basename
        $method = $dirname ? str_replace("/", "_", $dirname) . "_" . $pathinfo['basename'] : $pathinfo['basename'];

        // execute the method
        $this->response = $this->$method();

    }

    private function getExampleBody()
    {
        $responses = $this->route['method']->getResponses();
        foreach ($responses as $response) {
            try {
                return $response->getBodyByType('application/json')->getExample();
            } catch (Exception $e) {
                
            }
        }
    }

    public function getResponse()
    {
        return $this->response;
    }


    // Begin API methods
    private function correction ()
    {
        return $this->getExampleBody();
    }

    private function hello () 
    {
        $response = new stdClass();
        $response->message = "hello";
        $response->test = $request->params('test');
        return json_encode($response);
    }



}
