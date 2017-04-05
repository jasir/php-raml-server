<?php
/**
 * Processor for handling HTTP requests to the API defined in the RAML
 *
 */
namespace RamlServer;

use Exception;
use Slim\Http\Request;
use Slim\Http\Response;
use Tracy\Debugger;


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

	/** @var bool */
	private $throwNotExistingError = false;

	/**
	 * @var IControllerFactory
	 */
	private $controllerFactory;


	/**
	 * DefaultProcessor constructor.
	 * @param IControllerFactory $controllerFactory
	 */
	public function __construct(IControllerFactory $controllerFactory = null)
	{
		$this->controllerFactory = $controllerFactory ?: new DefaultControllerFactory();
	}


	/**
	 * @param ZeroRouter $router
	 * @param Request $request
	 * @param Response $response
	 * @param array $routeDefinition
	 * @return bool
	 * @throws RamlRuntimeException
	 */
	public function process(ZeroRouter $router, Request $request, Response $response, array $routeDefinition)
	{
		$this->routeDefinition = $routeDefinition;
		$this->request = $request;
		$this->response = $response;
		$this->router = $router;

		// Create controller class
		$controller = $this->controllerFactory->create($router, $request, $response, $routeDefinition);
		if (!$controller) {
			return false;
		}
		$methodName = $this->controllerFactory->generateMethodName($this->routeDefinition['type'], $this->routeDefinition['path']);

		if (!method_exists($controller, $methodName)) {
			if ($this->throwNotExistingError) {
				$className = get_class($controller);
				throw new RamlRuntimeException("Not existing method {$className}:{$methodName}");
			}
			return false;
		}

		// Validate the request
		try {
			RequestValidator::validate($this->request, $this->routeDefinition['method']);
		} catch (Exception $e) {
			// If validation is not successful, then return 400 Bad Request
			$this->response->setStatus(400);
			$this->prepareErrorResponse($e);
			return true;
		}

		// Process the request
		try {
			$data = $controller->$methodName();
			if ($data !== null) {
				// Standardize the response format
				$this->prepareResponse($data);
			}


		} catch (Exception $e) {
			// If request is not successful, then return 500 Internal error
			$this->response->setStatus(500);
			$this->prepareErrorResponse($e);
			return true;
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
	 */
	private function prepareResponse($data)
	{
		$response = new \stdClass();
		$response->status = $this->response->getStatus();
		$response->success = $this->response->isOk();
		$response->data = $data;
		$response = json_encode($response);
		$this->response->setBody($response);
	}


	/**
	 * Build error response by given exception
	 *
	 * @see self::prepareResponse()
	 * @param Exception $exception
	 */
	private function prepareErrorResponse(Exception $exception)
	{
		if (Debugger::$productionMode) {
			return $this->prepareResponse([
				'message' => 'Internal server error',
			]);
		}

		$this->prepareResponse([
			'code' => $exception->getCode(),
			'message' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'previous' => $exception->getPrevious(),
			'trace' => $exception->getTraceAsString(),
		]);
	}

}
