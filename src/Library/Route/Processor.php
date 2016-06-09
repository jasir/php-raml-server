<?php
/**
 * Processor for handling HTTP requests to the API defined in the RAML
 *
 */
 namespace RamlServer;

use MissingBodyException;
use MissingHeaderException;
use MissingQueryParameterException;
use Slim\Helper\Set;
use Slim\Http\Request;
use Slim\Http\Response;


final class Processor
{

    /**
     * The parsed RAML definition for the route that we are processing
     * @var array
     */
    private $routeDefinition;
    /**
     * The request object
     * @var Request
     */
    private $request;
    /**
     * The response object
     * @var Response
     */
    private $response;

    /** @var Set */
    private $appContainer;
    /**
     * @var ZeroRouter
     */
    private $router;


    /**
     * @param ZeroRouter $router
     * @param Set $appContainer
     * @param array $routeDefinition The parsed RAML definition for the route that we are processing
     */
    public function __construct(ZeroRouter $router,  Set $appContainer, array $routeDefinition)
    {
        $this->routeDefinition = $routeDefinition;
        $this->request = $appContainer->get('request');
        $this->response = $appContainer->get('response');
        $this->appContainer = $appContainer;
        $this->router = $router;
    }


    public function process()
    {
        // Create controller class

        $controllerClassName = $this->generateClassName($this->router->getApiName());
        $methodName = $this->generateMethodName($this->routeDefinition['type'], $this->routeDefinition['path']);

        $controller = class_exists($controllerClassName) ? $controllerClassName($this->appContainer, $this->routeDefinition) : null;

        $requestedExample = $this->request->headers->get("X-Http-Example");

        if ($requestedExample !== null || !method_exists($controller, $methodName || $controller === null)) {
            $this->processInMockMode($requestedExample);
        } else {
            $this->processInNormalMode($methodName, $controller);
        }
    }


    /**
     * Build the status object which has:
     *  `status` - http status code
     *  `success` - a truthy http status code?
     *  `data` - the data we want to return
     *
     *  Then the response body
     *
     * @param  jsonObject $data what you want to go back in the data part of the response
     * @return string the final content that was set to the response body
     */
    private function prepareResponse($data)
    {
        $response = new \stdClass();
        $response->status = $this->response->getStatus();
        $response->success = $this->response->isOk();
        $response->data = $data;
        $response = json_encode($response);
        $this->response->setBody($response);
        return $response;
    }


    /**
     * Processes request in mock mode, ie. returns exampla or schema
     * @param $httpExampleCode
     */
    private function processInMockMode($httpExampleCode)
    {
        $httpExampleCode = $httpExampleCode ?: 200;

        if ($this->request->headers->get("X-Http-Schema") == 1) {
            $this->sendSchema($httpExampleCode);
        } else {
            $this->sendExample($httpExampleCode);
        }

        // Set the status code of the response to the one the user wants to see
        $this->appContainer['response']->setStatus($httpExampleCode);
    }


    /**
     * @param $methodName
     * @param $controller
     */
    private function processInNormalMode($methodName, $controller)
    {
        try {
            // Validate the request
            $this->validateRequest();
            // Standardize the response format
            $this->prepareResponse($controller->$methodName());

        } catch (\Exception $e) {
            // If validation is not successful, then return 400 Bad Request
            $this->response->setStatus(400);
            $this->response->setBody($e->getMessage());
        }
    }


    /**
     * @param $httpExampleCode
     */
    private function sendSchema($httpExampleCode)
    {
        $schemaContent = $this->getSchemaResponseBody($httpExampleCode);
        $this->response->setBody(
            $schemaContent
        );
    }


    /**
     * @param $httpExampleCode
     */
    private function sendExample($httpExampleCode)
    {
        $exampleContent = $this->getExampleResponseBody($httpExampleCode);
        $this->response->setBody(
            $exampleContent
        );
    }


    /**
     * @param $apiName
     * @return mixed
     */
    private function generateClassName($apiName)
    {
        return str_replace("_", "", ucwords($apiName, "_"));
    }


    /**
     * Run validation on headers, query parameters, and body against the route definition,
     * verifying that required items exist. It returns nothing, but throws Exceptions if
     * a validation does not pass
     */
    private function validateRequest()
    {

        // validate headers
        foreach ($this->routeDefinition["method"]->getHeaders() as $namedParameter) {

            if ($namedParameter->isRequired() === true) {
                if (!in_array($namedParameter->getKey(), $this->request->headers->keys())) {
                    $message = array();
                    $message["missing_header"][$namedParameter->getKey()] = $namedParameter->getDescription();
                    throw new MissingHeaderException(json_encode($message));
                }
            }
        }

        // validate query parameters
        foreach ($this->routeDefinition["method"]->getQueryParameters() as $namedParameter) {
            if ($namedParameter->isRequired() === true) {
                if (!in_array($namedParameter->getKey(), array_keys($this->request->params()))) {
                    $message = array();
                    $message["missing_parameter"][$namedParameter->getKey()] = $namedParameter->getDescription();
                    throw new MissingQueryParameterException(json_encode($message));
                }
            }
        }

        // validate body
        $schema = null;
        try {
            $schema = $this->routeDefinition["method"]->getBodyByType("application/json")->getSchema();
        } catch (Exception $e) {
        }

        if (!is_null($schema)) {

            if ($schema->getJsonObject()->required) {
                if ($this->request->getBody() == "") {
                    $message = array();
                    $message["missing_body"]["schema"] = json_decode($schema->__toString());
                    throw new MissingBodyException(json_encode($message));
                }
            }
        }

    }


    /**
     * @param  $responseCode integer The HTTP code of the route definition for which we want to extract the example defined in the RAML for this API
     * @return string
     */
    private function getExampleResponseBody($responseCode = 200)
    {
        $responses = $this->routeDefinition["method"]->getResponses();
        try {
            return $responses[$responseCode]->getBodyByType("application/json")->getExample();
        } catch (Exception $e) {
            return null;
        }
    }


    /**
     * @param  $responseCode integer The HTTP code of the route definition for which we want to extract the schema defined in the RAML for this API
     * @return string
     */
    private function getSchemaResponseBody($responseCode = 200)
    {
        $responses = $this->routeDefinition["method"]->getResponses();
        try {
            return $responses[$responseCode]->getBodyByType("application/json")->getSchema();
        } catch (Exception $e) {
            return null;
        }
    }


    /**
     * @param $type
     * @param $path
     * @return string
     */
    private function generateMethodName($type, $path)
    {
        // Get the method name
        $pathInfo = pathinfo($path);
        // Trim the leading slash
        $dirName = ltrim($pathInfo["dirname"], "/");
        $dirName = ltrim($dirName, "\\");

        // Replace slashes with underscores and append basename
        $method = strtolower($type) . "_" . ($dirName ? str_replace("/", "_", $dirName) . "_" . $pathInfo["basename"] : $pathInfo["basename"]);
        $method = lcfirst(str_replace("_", "", ucwords($method, "_")));
        return $method;
    }


}
