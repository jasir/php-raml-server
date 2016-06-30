<?php


namespace RamlServer;


use Slim\Http\Request;
use Slim\Http\Response;

interface IProcessor
{
	/**
	 * @param ZeroRouter $zeroRouter
	 * @param Request $request
	 * @param Response $response
	 * @param array $route
	 * @return bool
	 */
	public function process(ZeroRouter $zeroRouter, Request $request, Response $response, array $route);
}