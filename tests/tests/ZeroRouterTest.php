<?php


namespace RamlServer;


class ZeroRouterTest extends \PHPUnit_Framework_TestCase
{

    public function test_function()
    {
        $options = [
            'server' => 'www.api.com',
            'apiUriPart' => 'api',
            'ramlDir' => '/path/to/raml'
        ];

        $router = new ZeroRouter(
            $options,
            'www.api.com/api/some-api-here/v1.0/users/logged/?q=1'
        );

        $this->assertTrue($router->isApiRoute());

        $this->assertEquals('api', $router->getApiUriPart());
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
            'ramlDir' => '/path/to/raml'
        ];

        $router = new ZeroRouter(
            $options,
            'www.api.com/cms/neco/clanek'
        );

        $this->assertFalse($router->isApiRoute());


    }

}
