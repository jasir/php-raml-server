<?php

namespace RamlServer;

use Slim\Slim;

class NeverAuthenticator implements IAuthenticator
{

	/**
	 * @param ZeroRouter $router
	 * @return bool
	 */
	public function authenticate(ZeroRouter $router)
	{
		return false;
	}
}