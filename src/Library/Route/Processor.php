<?php

require_once("Library/Route/Methods.php");
require_once("Library/Exception/Route/MissingQueryParameterException.php");
require_once("Library/Exception/Route/MissingBodyException.php");
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
        $method = strtolower($route['type']) . "_" . ($dirname ? str_replace("/", "_", $dirname) . "_" . $pathinfo['basename'] : $pathinfo['basename']);

        $methods = new Methods($appContainer, $route);
        // check if example response is requested
        if ($this->appContainer['request']->headers->get('Example-Response-Body')) {
            $this->response = $this->getExampleResponseBody($this->appContainer['request']->headers->get('Example-Response-Code'));
            $appContainer['response']->setStatus($this->appContainer['request']->headers->get('Example-Response-Code'));
        } else {
            $this->validateRequest();
            $this->response = $methods->$method();
        }
    }

    private function validateRequest()
    {
        foreach ($this->route['method']->getQueryParameters() as $namedParameter) {
            if( $namedParameter->isRequired() ){
                if (!in_array($namedParameter->getKey(), array_keys($this->appContainer['request']->params()))) {
                    throw new MissingQueryParameterException($namedParameter->getKey() . ": ". $namedParameter->getDescription());
                }   
            }
        }

        $schema = null;
        try {
            $schema = $this->route['method']->getBodyByType('application/json')->getSchema();
        } catch (Exception $e) {
        }

        if(!is_null($schema)){
            if($schema->getJsonObject()->required) {
                if ($this->appContainer['request']->getBody()=='') {
                    throw new MissingBodyException($schema->__toString());
                }
            }    
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
