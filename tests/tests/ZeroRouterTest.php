<?php


namespace RamlServer;


use Slim\Environment;
use Slim\Slim;

class ZeroRouterTest extends RamlServerTestCase
{

	public function test_function()
	{
		$options = [
			'server' => 'www.api.com',
			'apiUriPart' => 'api',
			'ramlDir' => '/path/to/raml',
			'ramlUriPart' => 'raml',
		];

		$router = new ZeroRouter(
			$options,
			'www.api.com/api/some-api-here/v1.0/users/logged/?q=1'
		);

		$this->assertTrue($router->isApiRequest());

		$this->assertEquals('some-api-here', $router->getApiName());
		$this->assertEquals('v1.0', $router->getVersion());
		$this->assertEquals('/path/to/raml', $router->getOption('ramlDir'));
		$this->assertEquals('/path/to/raml/some-api-here/v1.0/index.raml', $router->getApiIndexFile());

		$router = new ZeroRouter(
			$options,
			'www.api.com/api/some-api-here/users/logged?q=1'
		);

		$this->assertFalse($router->isApiRequest());

	}


	public function test_that_it_not_catches_different_api()
	{
		$options = [
			'server' => 'www.api.com',
			'apiUriPart' => 'api',
			'ramlDir' => '/path/to/raml',
			'ramlUriPart' => 'raml',
		];

		$router = new ZeroRouter(
			$options,
			'www.api.com/cms/neco/clanek'
		);

		$this->assertFalse($router->isApiRequest());
	}


	public function test_isRamlRequest()
	{
		$options = [
			'server' => 'www.api.com',
			'apiUriPart' => 'api',
			'ramlUriPart' => 'raml',
			'ramlDir' => '/path/to/raml'
		];

		$router = new ZeroRouter(
			$options,
			'www.api.com/raml/some-api-here/v1.0/index.raml'
		);

		$this->assertFalse($router->isApiRequest());
		$this->assertTrue($router->isRamlRequest());
		$this->assertEquals('some-api-here', $router->getApiName());
		$this->assertEquals('v1.0', $router->getVersion());
		$this->assertEquals('/path/to/raml/some-api-here/v1.0/index.raml', $router->getApiIndexFile());
		$this->assertEquals('index.raml', $router->getRequestedRamlFile());
	}


	public function test_getOption()
	{
		$options = [
			'server' => 'www.api.com',
			'apiUriPart' => 'api',
			'ramlDir' => '/path/to/raml',
			'ramlUriPart' => 'raml'
		];

		$router = new ZeroRouter(
			$options,
			'www.api.com/api/some-api-here/v1.0/users/logged/?q=1'
		);

		$this->assertEquals('www.api.com', $router->getOption('server'));
		$this->assertEquals('default', $router->getOption('none', 'default'));

		try {
			$router->getOption('none');
		} catch (\InvalidArgumentException $e) {
			$this->assertInstanceOf(\InvalidArgumentException::class, $e);
			$catch = true;
		}

		$this->assertTrue($catch);

	}


	public function requestsProvider()
	{
		return [
			[
				'uri' => '/api/test-api/v1.0/greet?who=Jaroslav',
				'expectedOutput' => '{"status":200,"success":true,"data":{"greetings":"Hello, Jaroslav"}}'
			],
			[
				'uri' => '/api/test-api/v1.0/kill?who=Mocked',
				'expectedOutput' => '{"status":200,"success":true,"data":{"killed":"I shot John Doe!"}}'
			],

		];
	}


	/**
	 * @dataProvider requestsProvider
	 * @param $uri
	 * @param $expectedOutput
	 */
	public function test_serveApi($uri, $expectedOutput)
	{
		$server = 'www.api.com';

		$pathInfo = $uri;
		$pos = strpos($uri, '?');
		if ($pos !== false) {
			$pathInfo = substr($pathInfo, 0, $pos);
		}

		list($pathInfo, $queryString) = explode('?', $uri, 2);

		$defaultHeaders = [
			'SERVER_NAME' => 'www.api.com',
			'REQUEST_SCHEME' => 'http',
			'REQUEST_URI' => $uri,
			'PATH_INFO' => $pathInfo,
			'QUERY_STRING' => $queryString,
			'slim.url_scheme' => 'http'
		];

		Environment::mock($defaultHeaders);

		$options = [
			'server' => 'http://www.api.com',
			'apiUriPart' => 'api',
			'ramlDir' => __DIR__ . '/../assets/raml',
			'ramlUriPart' => 'raml'
		];


		$url = 'http://www.api.com' . $uri;

		$router = new ZeroRouter($options, $url);

		$router->addProcessor(new MockProcessorFactory(false));
		$router->addProcessor(new DefaultProcessorFactory('RamlServer'));
		$router->addProcessor(new MockProcessorFactory(true));

		$this->assertTrue($router->isApiRequest());
		$this->assertFalse($router->isRamlRequest());
		$this->assertEquals('test-api', $router->getApiName());

		ob_start();
		$router->serveApi();
		$content = ob_get_clean();

		$output = $this->normalizeWhitespaces(json_encode(json_decode($content)));
		$expected = $this->normalizeWhitespaces(json_encode(json_decode($expectedOutput)));

		$this->assertEquals($expected, $output);

	}

}
