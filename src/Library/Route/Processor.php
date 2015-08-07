<?php

require_once("Library/Route/Methods.php");
require_once("Library/Exception/Route/MissingBodyException.php");
require_once("Library/Exception/Route/MissingHeaderException.php");
require_once("Library/Exception/Route/MissingQueryParameterException.php");
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
        if ($this->appContainer['request']->headers->get('X-Http-Example') !== null) {
            if ($this->appContainer['request']->headers->get('X-Http-Schema') == 1) {
                $this->appContainer['response']->setBody(
                    $this->getSchemaResponseBody($this->appContainer['request']->headers->get('X-Http-Example'))
                );
            } else {
                $this->appContainer['response']->setBody(
                    $this->getExampleResponseBody($this->appContainer['request']->headers->get('X-Http-Example'))
                );
            }

            $appContainer['response']->setStatus($this->appContainer['request']->headers->get('X-Http-Example'));
        } else {
            try {
                $this->validateRequest();
                $this->prepareResponse($methods->$method());
            } catch (\Exception $e) {
                $this->appContainer['response']->setStatus(400);
                $this->appContainer['response']->setBody($e->getMessage());
            }

        }
    }

    private function validateRequest()
    {

        foreach ($this->route['method']->getHeaders() as $namedParameter) {
            if( $namedParameter->isRequired() ){
                if (!in_array($namedParameter->getKey(), array_keys($this->appContainer['request']->headers->keys()))) {
                    $message = array();
                    $message['missing_header'][$namedParameter->getKey()] = $namedParameter->getDescription();
                    throw new MissingHeaderException(json_encode($message));
                }
            }
        }

        foreach ($this->route['method']->getQueryParameters() as $namedParameter) {
            if( $namedParameter->isRequired() ){
                if (!in_array($namedParameter->getKey(), array_keys($this->appContainer['request']->params()))) {

                    $message = array();
                    $message['missing_parameter'][$namedParameter->getKey()] = $namedParameter->getDescription();
                    throw new MissingQueryParameterException(json_encode($message));
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
                    $message = array();
                    $message['missing_body']['schema'] = json_decode($schema->__toString());
                    throw new MissingBodyException(json_encode($message));
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

    private function getSchemaResponseBody($responseCode = 200)
    {
        $responses = $this->route['method']->getResponses();
        try {
            return $responses[$responseCode]->getBodyByType('application/json')->getSchema();
        } catch (Exception $e) {
            return null;
        }
    }

    public function prepareResponse($data)
    {
        $response = new stdClass();
        $response->status = $this->appContainer['response']->getStatus();
        $response->success = $this->appContainer['response']->isOk();
        $response->data = $data;
        $this->appContainer['response']->setBody(json_encode($response));
    }

    public function getResponse()
    {
        return $this->response;
    }

}
