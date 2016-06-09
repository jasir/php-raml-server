<?php


namespace RamlServer;


class ZeroRouterTest extends \PHPUnit_Framework_TestCase
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
        $this->assertEquals('/path/to/raml', $router->getRamlRootDirectory());
        $this->assertEquals('/path/to/raml/some-api-here/v1.0/index.raml', $router->getApiIndexFile());

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

}
