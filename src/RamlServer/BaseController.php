<?php

namespace RamlServer;

use Slim\Http\Request;
use Slim\Http\Response;

abstract class BaseController implements IController
{

	protected $request;
	/**
	 * The response object
	 * @var \Slim\Http\Response
	 */
	protected $response;
	/**
	 * @var ZeroRouter
	 */
	private $router;
	/**
	 * @var array
	 */
	private $route;


	/**
	 * @param Request $request
	 * @param Response $response
	 * @param ZeroRouter $router
	 * @param array $route
	 */
	public function __construct(Request $request, Response $response, ZeroRouter $router, array $route)
	{
		$this->request = $request;
		$this->response = $response;
		$this->router = $router;
		$this->route = $route;
	}

}
