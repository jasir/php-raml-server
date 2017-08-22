<?php

namespace RamlServer;


class NeonLoaderTest extends RamlServerTestCase
{
	public function test_neon_is_correctly_loaded()
	{
		$loader = new NeonLoader();

		$output = $loader->loadFile(__DIR__ . '/../assets/raml/test-api/v1.0/neon-example.neon');
		$this->assertEquals('{"hello":{"world":true}}', $output);
	}
}
