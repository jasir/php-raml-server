<?php


namespace RamlServer;


class DefaultProcessorFactory implements IProcessorFactory
{
	/**
	 * @var string
	 */
	private $namespace;


	/**
	 * @param $namespace
	 */
	public function __construct($namespace = null)
	{
		$this->namespace = $namespace;
	}


	/**
	 * @return DefaultProcessorFactory
	 */
	public function create()
	{
		return new DefaultProcessor($this->namespace);
	}
}