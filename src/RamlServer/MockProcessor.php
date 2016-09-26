<?php
/**
 * Processor for handling HTTP requests to the API defined in the RAML
 *
 */
namespace RamlServer;

use Exception;
use Nette\Utils\Json;
use Slim\Http\Request;
use Slim\Http\Response;


/**
 * Class Processor
 * @package RamlServer
 */
final class MockProcessor implements IProcessor
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
	 * @var bool
	 */
	private $handleAlways;


	/**
	 * @param bool $handleAlways If true, no X-Http-Example header needed to serve example
	 */
	public function __construct($handleAlways)
	{

		$this->handleAlways = $handleAlways;
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


		$requestedExampleResponseCode = $this->request->headers->get('X-Http-Example');

		if ($this->handleAlways === false && $requestedExampleResponseCode === null) {
			return false;
		}

		$requestedExampleResponseCode = $requestedExampleResponseCode ?: 200;

		try {
			RequestValidator::validate($request, $routeDefinition);
		} catch (RamlRuntimeException $e) {
			$this->sendError($e);
			return true;
		}


		if ($this->request->headers->get('X-Http-Schema') == 1) {
			$this->sendSchema($requestedExampleResponseCode);
		} else {
			$this->sendExample($requestedExampleResponseCode);
		}

		// Set the status code of the response to the one the user wants to see
		$this->response->setStatus($requestedExampleResponseCode);

		return true;
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
	 * @param RamlRuntimeException $e
	 * @throws \Nette\Utils\JsonException
	 */
	private function sendError(RamlRuntimeException $e)
	{
		$this->response->setStatus(500);
		$this->response->setBody(Json::encode(
			[
				'error' => $e->getMessage(),
				'success' => false,
			]
		));
	}

}
