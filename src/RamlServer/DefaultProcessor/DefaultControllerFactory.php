<?php

namespace RamlServer;


use Slim\Http\Request;
use Slim\Http\Response;

class DefaultControllerFactory implements IControllerFactory
{
	/**
	 * @var string
	 */
	private $classNamespace = '';


	/**
	 * DefaultControllerFactory constructor.
	 * @param string $classNamespace
	 */
	public function __construct($classNamespace = '')
	{
		$this->classNamespace = $classNamespace;
}


	/**
	 * @param ZeroRouter $router
	 * @param Request $request
	 * @param Response $response
	 * @param array $routeDefinition
	 * @return IController
	 */
	public function create(ZeroRouter $router, Request $request, Response $response, array $routeDefinition)
	{
		$className = $this->generateClassName($router->getApiName(), $this->classNamespace);

		$controller = class_exists($className)
			? new $className($request, $response, $router, $routeDefinition)
			: null;

		return $controller;
	}


	/**
	 * @param $apiName
	 * @param $namespace
	 * @return string
	 */
	static public function generateClassName($apiName, $namespace = null)
	{
		$className = self::snakeToCamel($apiName);
		return $namespace ? $namespace . '\\' . $className : $className;
	}


	/**
	 * ie. getSomething
	 * @param $httpMethod
	 * @param $path
	 * @return string
	 */
	public function generateMethodName($httpMethod, $path)
	{
		return strtolower($httpMethod) . self::snakeToCamel($path, ['/', '.', '_', '-']);
	}


	/**
	 * Converts snake_case to CamelCase, accepts both - and _
	 * @param $snakeName
	 * @param array $snakeChars
	 * @return string
	 */
	public static function snakeToCamel($snakeName, array $snakeChars = ['_', '-', '.'])
	{
		return str_replace(' ', '', ucwords(str_replace($snakeChars, ' ', $snakeName)));
	}

}