<?php

namespace RamlServer;

abstract class RamlServerTestCase extends \PHPUnit_Framework_TestCase
{
	/** @var \Mockista\Registry */
	protected $mockista;


	/**
	 * Removes unnecessary whitespaces for easier comparing
	 * @param string $s
	 * @returns string
	 */
	protected static function normalizeWhitespaces($s)
	{
		$s = str_replace("\n", ' ', $s);
		$s = str_replace("\r", ' ', $s);
		$s = str_replace("\t", ' ', $s);
		$s = trim($s);
		$s = preg_replace('/\s+/x', ' ', $s);
		return $s;
	}


	protected function setUp()
	{
		$this->mockista = new \Mockista\Registry();
	}


	protected function tearDown()
	{
		$this->mockista->assertExpectations();
	}


	/**
	 * @param $closure
	 * @param string $expectedExceptionClass
	 * @param string $expectedMessage
	 */
	protected function assertException($closure, $expectedExceptionClass = 'Exception', $expectedMessage = NULL)
	{
		try {
			call_user_func($closure);
		} catch (\Exception $e) {
			if (!$e instanceOf $expectedExceptionClass) {
				$this->assertInstanceOf($expectedExceptionClass, $e);
			}
			if ($expectedMessage) {
				$this->assertEquals($expectedMessage, $e->getMessage());
			}
			return;
		}
		$this->fail("Expected exception $expectedExceptionClass was not thrown");
	}


	/**
	 * @param $suffix
	 * @param $actual
	 * @param string $message
	 */
	protected function assertRoughlyEquals($suffix, $actual, $message = '')
	{
		$suffix = self::normalizeWhitespaces($suffix);
		$actual = self::normalizeWhitespaces($actual);
		$this->assertEquals($suffix, $actual, $message);
	}


	/**
	 * @param $suffix
	 * @param $actual
	 * @param string $message
	 */
	protected function assertRoughlyEndsWith($suffix, $actual, $message = '')
	{
		$suffix = self::normalizeWhitespaces($suffix);
		$actual = self::normalizeWhitespaces($actual);
		$actual = substr($actual, -strlen($suffix));
		parent::assertEquals($suffix, $actual, $message);
	}

}
