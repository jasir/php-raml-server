<?php


namespace RamlServer;


interface IProcessorFactory
{
	/**
	 * @return IProcessor
	 */
	public function create();
}