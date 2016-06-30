<?php


namespace RamlServer;


class MockProcessorFactory implements IProcessorFactory
{
	/**
	 * @var bool
	 */
	private $catchAlways;


	/**
	 * MockProcessorFactory constructor.
	 * @param bool $catchAlways
	 */
	public function __construct($catchAlways)
	{
		$this->catchAlways = (bool) $catchAlways;
	}


	/**
	 * @return MockProcessorFactory
	 */
	public function create()
	{
		return new MockProcessor($this->catchAlways);
	}
}