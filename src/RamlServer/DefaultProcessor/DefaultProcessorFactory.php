<?php


namespace RamlServer;

class DefaultProcessorFactory implements IProcessorFactory
{


	/**
	 * @var IControllerFactory
	 */
	private $controllerFactory;


	/**
	 * @param IControllerFactory $controllerFactory
	 */
	public function __construct(IControllerFactory $controllerFactory = null)
	{
		$this->controllerFactory = $controllerFactory;
	}


	/**
	 * @return DefaultProcessor
	 */
	public function create()
	{
		return new DefaultProcessor($this->controllerFactory);
	}
}