<?php


namespace RamlServer;


use Raml\Exception\InvalidSchemaException;
use Raml\Parser;
use Slim\Environment;
use Slim\Http\Request;

class RequestValidatorTest extends RamlServerTestCase
{
	public function test_Validate_needParameter()
	{
		$path = 'needParameter';
		list($request, $resource) = $this->prepareRequestAndResource($path);
		$this->assertException(function () use ($request, $resource) {
			RequestValidator::validate($request, $resource->getMethod('GET'));
		}, MissingQueryParameterException::class);

	}

	public function test_Validate_jsonBody()
	{
		$path = 'validateBodyIncluded';
		$body = <<<EOF
        {
            "songId": "550e8400-e29b-41d4-a716-446655440000",
            "songTitle": "Get Lucky",
            "albumId": "183100e3-0e2b-4404-a716-66104d440550"
          }
EOF;

		list($request, $resource) = $this->prepareRequestAndResource($path, 'POST', $body);
		RequestValidator::validate($request, $resource->getMethod('POST'));
	}

	public function test_Validate_jsonBody_throws()
	{
		$path = 'validateBody';
		$body = <<<EOF
        {
            "songId": "550e8400-e29b-41d4-a716-446655440000",
            "songTitle": "Get Lucky"
          }
EOF;

		list($request, $resource) = $this->prepareRequestAndResource($path, 'POST', $body);
		$this->assertException(function () use ($request, $resource) {
			RequestValidator::validate($request, $resource->getMethod('POST'));
		}, InvalidSchemaException::class, 'Invalid Schema.');
	}


	public function test_Neon_works_as_example()
	{
		$definition = $this->getParsedDefinition();

	}

	
	/**
	 * @return \Raml\ApiDefinition
	 */
	protected function getParsedDefinition()
	{
		$parser = new Parser();
		$dir = __DIR__ . '/../assets/raml/test-api/v1.0';
		return $parser->parseFromString(file_get_contents($dir . '/index.raml'), $dir);
	}
	
	

	/**
	 * @param $path
	 * @param string $method
	 * @param string $rawBody
	 * @return array
	 */
	protected function prepareRequestAndResource($path, $method = 'GET', $rawBody = '')
	{
		$this->prepareMockedSlimEnvironment("/test-api/v1.0/$path", $method, $rawBody);
		$request = new Request(Environment::getInstance());
		$definitions = $this->getParsedDefinition();
		$resource = $definitions->getResourceByPath('/' . $path);
		return array($request, $resource);
	}


}
