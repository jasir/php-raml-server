<?php


namespace RamlServer;


class ProcessorHelpersTest extends \PHPUnit_Framework_TestCase
{
	public function test_snakeToCamel() {
		$this->assertEquals('AaaBbbCcc', ProcessorHelpers::snakeToCamel('aaa_bbb_ccc'));
		$this->assertEquals('AaaBbbCcc', ProcessorHelpers::snakeToCamel('aaa-bbb-ccc'));
	}


}
