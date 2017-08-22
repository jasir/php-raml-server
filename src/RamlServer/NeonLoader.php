<?php

namespace RamlServer;

use Nette\Neon\Neon;
use Raml\FileLoader\FileLoaderInterface;
use Unirest\Exception;

class NeonLoader implements FileLoaderInterface
{

	/**
	 * NeonLoader constructor.
	 */
	public function __construct()
	{
	}


	/**
	 * Load a file from a path and return a string
	 *
	 * @param string $filePath
	 *
	 * @return string
	 */
	public function loadFile($filePath)
	{
		$content = file_get_contents($filePath);

		return json_encode(Neon::decode($content));
	}


	/**
	 * Get a list of valid file extensions
	 *
	 * @return string[]
	 */
	public function getValidExtensions()
	{
		return ['neon'];
	}
}