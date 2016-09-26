<?php


namespace RamlServer;


use Raml\Parser;
use Slim\Environment;
use Slim\Http\Request;

class RequestValidatorTest extends RamlServerTestCase
{
	public function test_Validate()
	{
		$path = 'needParameter';
		list($request, $resource) = $this->prepareRequestAndResource($path);
		$this->assertException(function () use ($request, $resource) {
			RequestValidator::validate($request, $resource->getMethod('GET'));
		}, MissingQueryParameterException::class);

	}


	/**
	 * @return \Raml\ApiDefinition
	 */
	protected function getParsedDefinition()
	{
		$parser = new Parser();
		$dir = __DIR__ . '/../assets/raml/test-api';
		return $parser->parseFromString(file_get_contents($dir . '/v1.0/index.raml'), $dir);
	}


	/**
	 * @param $path
	 * @return array
	 */
	protected function prepareRequestAndResource($path)
	{
		$this->prepareMockedSlimEnvironment("/test-api/v1.0/$path");
		$request = new Request(Environment::getInstance());
		$definitions = $this->getParsedDefinition();
		$resource = $definitions->getResourceByPath('/' . $path);
		return array($request, $resource);
	}


}
