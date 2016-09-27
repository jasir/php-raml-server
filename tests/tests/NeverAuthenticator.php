<?php

namespace RamlServer;

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