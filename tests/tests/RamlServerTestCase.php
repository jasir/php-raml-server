<?php

namespace RamlServer;

abstract class RamlServerTestCase extends \PHPUnit_Framework_TestCase
{
	/** @var \Mockista\Registry */
	protected $mockista;


	/**
	 * Removes unecessary whitespaces for easier comparing
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
	 * Asserts entity data equals, ignores updatedBy, createdBy, updated, created
	 * @param $expected
	 * @param $actual
	 * @param null $message
	 */
	protected function assertEntityDataEquals($expected, $actual, $message = null)
	{

		if ($expected === null) {
			$this->assertNull($actual);
			return;
		}

		$this->assertEquals(
			$this->nullifySystemProperties($expected),
			$this->nullifySystemProperties($actual), $message
		);

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


	private function nullifySystemProperties($array)
	{
		if (!is_array($array)) {
			return $array;
		}
		array_walk_recursive($array, function (&$value, $key) {
			if (in_array($key, ['updatedBy', 'createdBy', 'updated', 'created'])) {
				$value = null;
			}
		});
		return $array;
	}


}
