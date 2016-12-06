<?php


namespace RamlServer;


class DefaultControllerFactoryTest extends RamlServerTestCase
{

	public function test_generateClassName()
	{
		$factory = new DefaultControllerFactory();
		$this->assertEquals('TestApi', $factory->generateClassName('test-api'));
		$this->assertEquals('TestApi', $factory->generateClassName('test_api'));
		$this->assertEquals('Namespace\\TestApi', $factory->generateClassName('test-api', 'Namespace'));
	}


	public function test_generateMethodName()
	{
		$factory = new DefaultControllerFactory();
		$this->assertEquals('getSearch', $factory->generateMethodName('GET', '/search'));
		$this->assertEquals('getUsersSearch', $factory->generateMethodName('GET', '/users/search'));
		$this->assertEquals('getUsersNsSomethingBad', $factory->generateMethodName('GET', '/users/ns.something-bad'));

	}


	public function test_snakeToCamel()
	{
		$factory = new DefaultControllerFactory();
		$this->assertEquals('AaaBbbCcc', $factory->snakeToCamel('aaa_bbb_ccc'));
		$this->assertEquals('AaaBbbCcc', $factory->snakeToCamel('aaa-bbb-ccc'));
	}


}
