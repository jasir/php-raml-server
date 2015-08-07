<?php
/**
 * Processor for handling HTTP requests to the API defined in the RAML
 *
 */
require_once("Library/Route/Methods.php");
require_once("Library/Exception/Route/MissingBodyException.php");
require_once("Library/Exception/Route/MissingHeaderException.php");
require_once("Library/Exception/Route/MissingQueryParameterException.php");
use Slim\Helper\Set;


class Processor
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
     * @param Slim\Helper\Set $appContainer
     * @param array $route
     */
    public function __construct(Set $appContainer, array $route)
    {
        $this->route = $route;
        $this->configs = $appContainer->get("configs");
        $this->request = $appContainer->get("request");
        $this->response = $appContainer->get("response");


        // Get the method name
        $pathinfo = pathinfo ($this->route["path"]);
        // Trim the leading slash
        $dirname = ltrim ($pathinfo["dirname"], "/");
        // Replace slashes with underscores and append basename
        $method = strtolower($route["type"]) . "_" . ($dirname ? str_replace("/", "_", $dirname) . "_" . $pathinfo["basename"] : $pathinfo["basename"]);
        $method = lcfirst(str_replace("_", "", ucwords($method, "_")));

        // Invoke the class which containes the route method implementation from methods/{version}/{api_name}.php
        $methodsClassName = str_replace("_", "", ucwords($this->configs["api_name"], "_"));
        require_once("methods/" . $appContainer->get("apiDef")->getVersion() . "/" . $methodsClassName . ".php");
        $methodsClass = new $methodsClassName($appContainer, $route);

        // Check first if example response is requested, we can bypass validation and just return
        // the example response or response schema for a requested HTTP code
        // For example, you want to see what an example 200 response would be for a route, this will be returned as defined in the RAML for this API
        if ($this->request->headers->get("X-Http-Example") !== null) {
            // If the X-Http-Schema header is set to 1 then we return the response schema instead of the example
            if ($this->request->headers->get("X-Http-Schema") == 1) {
                $this->response->setBody(
                    $this->getSchemaResponseBody($this->request->headers->get("X-Http-Example"))
                );
            } else {
                $this->response->setBody(
                    $this->getExampleResponseBody($this->request->headers->get("X-Http-Example"))
                );
            }
            // Set the status code of the response to the one the user wants to see
            $appContainer["response"]->setStatus($this->request->headers->get("X-Http-Example"));
        } else {
            try {
                // Validate the request
                $this->validateRequest();
                // Standardize the response format
                $this->prepareResponse($methodsClass->$method());
            } catch (\Exception $e) {
                // If validation is not successful, then return 400 Bad Request
                $this->response->setStatus(400);
                $this->response->setBody($e->getMessage());
            }

        }
    }

    /**
     * Run validation on headers, query parameters, and body against the route definition,
     * verifying that required items exist. It returns nothing, but throws Exceptions if
     * a validation does not pass
     */
    private function validateRequest()
    {

        // validate headers
        foreach ($this->route["method"]->getHeaders() as $namedParameter) {
            if( $namedParameter->isRequired() ){
                if (!in_array($namedParameter->getKey(), array_keys($this->request->headers->keys()))) {
                    $message = array();
                    $message["missing_header"][$namedParameter->getKey()] = $namedParameter->getDescription();
                    throw new MissingHeaderException(json_encode($message));
                }
            }
        }

        // validate query parameters
        foreach ($this->route["method"]->getQueryParameters() as $namedParameter) {
            if( $namedParameter->isRequired() ){
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
            $schema = $this->route["method"]->getBodyByType("application/json")->getSchema();
        } catch (Exception $e) {
        }

        if(!is_null($schema)){

            if($schema->getJsonObject()->required) {
                if ($this->request->getBody()=="") {
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
        $responses = $this->route["method"]->getResponses();
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
        $responses = $this->route["method"]->getResponses();
        try {
            return $responses[$responseCode]->getBodyByType("application/json")->getSchema();
        } catch (Exception $e) {
            return null;
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
    public function prepareResponse($data)
    {
        $response = new stdClass();
        $response->status = $this->response->getStatus();
        $response->success = $this->response->isOk();
        $response->data = $data;
        $response = json_encode($response);
        $this->response->setBody($response);
        return $response;
    }


}
