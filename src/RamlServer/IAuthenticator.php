<?php

namespace RamlServer;

interface IAuthenticator
{
	/**
	 * @param ZeroRouter $router
	 * @return bool
	 */
	public function authenticate(ZeroRouter $router);
}