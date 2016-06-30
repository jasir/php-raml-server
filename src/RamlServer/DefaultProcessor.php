<?php
/**
 * Processor for handling HTTP requests to the API defined in the RAML
 *
 */
namespace RamlServer;

use Exception;
use Slim\Http\Request;
use Slim\Http\Response;


/**
 * Class DefaultProcessor
 *
 * Locates API Controller by name, locates method by name,
 * injects dependencies to it and calls the method.
 *
 * @package RamlServer
 */
final class DefaultProcessor implements IProcessor
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

	/**
	 * @var ZeroRouter
	 */
	private $router;
	/**
	 * @var string
	 */
	private $namespace;

	/** @var bool */
	private $throwNotExistingError = false;


	/**
	 * DefaultProcessor constructor.
	 * @param string $namespace
	 */
	public function __construct($namespace)
	{
		$this->namespace = $namespace;
	}


	/**
	 * @param ZeroRouter $router
	 * @param Request $request
	 * @param Response $response
	 * @param array $routeDefinition
	 * @return bool
	 */
	public function process(ZeroRouter $router, Request $request, Response $response, array $routeDefinition)
	{

		$this->routeDefinition = $routeDefinition;
		$this->request = $request;
		$this->response = $response;
		$this->router = $router;

		// Create controller class

		$className = $this->generateClassName();
		$methodName = $this->generateMethodName();

		$controller = class_exists($className)
			? new $className($request, $response, $router, $routeDefinition)
			: null;

		if (!$controller) {
			return false;
		}

		if (!method_exists($controller, $methodName)) {
			if ($this->throwNotExistingError) {
				throw new RamlRuntimeException("Not existing method {$className}:{$methodName}");
			}
			return false;
		}


		if ($controller) {
			try {
				// Validate the request
				$this->validateRequest();
				// Standardize the response format
				$this->prepareResponse($controller->$methodName());

			} catch (Exception $e) {
				// If validation is not successful, then return 400 Bad Request
				$this->response->setStatus(400);
				$this->response->setBody($e->getMessage());
			}
		}

		return true;
	}


	/**
	 * Build the status object which has:
	 *  `status` - http status code
	 *  `success` - a truthy http status code?
	 *  `data` - the data we want to return
	 *
	 *  Then the response body
	 *
	 * @param  \jsonObject $data what you want to go back in the data part of the response
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
	 * Run validation on headers, query parameters, and body against the route definition,
	 * verifying that required items exist. It returns nothing, but throws Exceptions if
	 * a validation does not pass
	 */
	private function validateRequest()
	{

		// validate headers
		/** @var $namedParameter */
		foreach ($this->routeDefinition["method"]->getHeaders() as $namedParameter) {

			if ($namedParameter->isRequired() === true) {
				if (!in_array($namedParameter->getKey(), $this->request->headers->keys(), true)) {
					$message = array();
					$message['missing_header'][$namedParameter->getKey()] = $namedParameter->getDescription();
					throw new MissingHeaderException(json_encode($message));
				}
			}
		}

		// validate query parameters
		foreach ($this->routeDefinition["method"]->getQueryParameters() as $namedParameter) {
			if ($namedParameter->isRequired() === true) {
				if (!in_array($namedParameter->getKey(), array_keys($this->request->params()), true)) {
					$message = array();
					$message['missing_parameter'][$namedParameter->getKey()] = $namedParameter->getDescription();
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
	 * @return string
	 */
	private function generateClassName()
	{
		$apiName = $this->router->getApiName();

		$words = explode('-', str_replace('_', '-', $apiName));
		$className = implode('', array_map(function ($word) {
			return ucfirst($word);
		}, $words));

		if ($this->namespace) {
			return $this->namespace . '\\' . $className;
		} else {
			return $className;
		}
	}


	/**
	 * ie. getSomething
	 * @return string
	 */
	private function generateMethodName()
	{
		$pathInfo = pathinfo($this->routeDefinition['path']);
		$dirName = ltrim($pathInfo['dirname'], '/');
		$dirName = ltrim($dirName, "\\");
		$method = strtolower($this->routeDefinition['type']) . '_' . ($dirName ? str_replace('/', '_', $dirName) . '_' . $pathInfo['basename'] : $pathInfo['basename']);
		$method = lcfirst(str_replace('_', '', ucwords($method, '_')));
		return $method;
	}


}
