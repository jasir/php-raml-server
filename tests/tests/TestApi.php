<?php


namespace RamlServer;


class TestApi extends BaseController
{
	public function getGreet()
	{
		return [
			'greetings' => 'Hello, ' . $this->request->get('who')
		];
	}

}