<?php


namespace RamlServer;


class ProcessorHelpers
{


	/**
	 * Converts snake_case to CamelCase, accepts both - and _
	 * @param $snakeName
	 * @return string
	 */
	public static function snakeToCamel($snakeName, $snakeChars = ['_', '-'])
	{
		return str_replace(' ', '', ucwords(str_replace($snakeChars, ' ', $snakeName)));
	}
}