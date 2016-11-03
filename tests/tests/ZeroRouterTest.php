<?php


namespace RamlServer;


use Nette\Utils\Json;

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

		$this->assertEquals(self::WWW_API_COM, $router->getOption('server'));
		$this->assertEquals('default', $router->getOption('none', 'default'));

		try {
			$catch = false;
			$router->getOption('none');
		} catch (\InvalidArgumentException $e) {
			$this->assertInstanceOf(\InvalidArgumentException::class, $e);
			$catch = true;
		}

		$this->assertTrue($catch);

	}


	public function test_authenticate()
	{
		$uri = '/api/test-api/v1.0/greet?who=Jaroslav';
		$this->prepareMockedSlimEnvironment($uri);
		$router = $this->createZeroRouter($uri);

		$authenticator = new NeverAuthenticator();
		$router->setAuthenticator($authenticator);
		$router->serveApi();
		$this->assertEquals(403, $router->getResponse()->getStatus());
		$this->assertEquals('Invalid security context', $router->getResponse()->getBody());
		
	}
	

	/**
	 * @return array
	 */
	public function requestsProvider()
	{
		return [
			[
				'uri' => '/api/test-api/v1.0/greet?who=Jaroslav',
				'expectedOutput' => '{"status":200,"success":true,"data":{"greetings":"Hello, Jaroslav"}}',
			],
			[
				'uri' => '/api/test-api/v1.0/kill?who=Mocked',
				'expectedOutput' => '{"status":200,"success":true,"data":{"killed":"I shot John Doe!"}}'
			],
			[
				'uri' => '/api/test-api/v1.0/needParameter',
				'expectedOutput' => '{ "error": "Missing required query parameter `fill`", "success": false }',
				'expectedCode' => 500,
			],

		];
	}


	/**
	 * @dataProvider requestsProvider
	 * @param $uri
	 * @param $expectedOutput
	 * @param int $expectedCode
	 * @throws RamlRuntimeException
	 * @throws \Nette\Utils\JsonException
	 */
	public function test_serveApi($uri, $expectedOutput, $expectedCode = 200)
	{
		$this->prepareMockedSlimEnvironment($uri);
		$router = $this->createZeroRouter($uri);

		ob_start();
		$router->serveApi();
		$content = ob_get_clean();

		$output = $this->normalizeWhitespaces(Json::encode(Json::decode($content), Json::PRETTY));
		$expected = $this->normalizeWhitespaces(Json::encode(Json::decode($expectedOutput), Json::PRETTY));

		$this->assertEquals($expected, $output);

		$this->assertEquals($expectedCode, $router->getResponse()->getStatus());

	}


	/**
	 * @param $uri
	 * @return ZeroRouter
	 * @throws RamlRuntimeException
	 */
	protected function createZeroRouter($uri)
	{
		$options = [
			'server' => self::WWW_API_COM, //www.api.com
			'apiUriPart' => 'api',
			'ramlDir' => __DIR__ . '/../assets/raml',
			'ramlUriPart' => 'raml'
		];


		$url = self::WWW_API_COM . $uri;
		$router = new ZeroRouter($options, $url);

		$router->addProcessor(new MockProcessorFactory(false));
		$router->addProcessor(new DefaultProcessorFactory(new DefaultControllerFactory('RamlServer')));
		$router->addProcessor(new MockProcessorFactory(true));

		$this->assertTrue($router->isApiRequest());
		$this->assertFalse($router->isRamlRequest());
		$this->assertEquals('test-api', $router->getApiName());
		return $router;
	}


}
