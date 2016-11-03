<?php

namespace RamlServer;

use Slim\Http\Request;
use Slim\Http\Response;

interface IControllerFactory
{
	/**
	 * @param ZeroRouter $router
	 * @param Request $request
	 * @param Response $response
	 * @param array $routeDefinition
	 * @return mixed
	 */
	public function create(ZeroRouter $router, Request $request, Response $response, array $routeDefinition);
}