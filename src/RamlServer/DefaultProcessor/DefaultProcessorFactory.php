<?php


namespace RamlServer;


use Nette\DI\Container;

class DefaultProcessorFactory implements IProcessorFactory
{
	/**
	 * @var string
	 */
	private $namespace;

	/**
	 * @var Container
	 */
	private $container;


	/**
	 * @param Container $container
	 * @param $namespace
	 */
	public function __construct(Container $container, $namespace = null)
	{
		$this->namespace = $namespace;
		$this->container = $container;
	}


	/**
	 * @return DefaultProcessor
	 */
	public function create()
	{
		return new DefaultProcessor($this->namespace, $this->container);
	}
}