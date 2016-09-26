<?php


namespace RamlServer;


use Exception;
use Slim\Http\Request;

class ProcessorHelpers
{

	/**
	 * Run validation on headers, query parameters, and body against the route definition,
	 * verifying that required items exist. It returns nothing, but throws Exceptions if
	 * a validation does not pass
	 * @param Request $request
	 * @param array $routeDefinition
	 * @throws MissingBodyException
	 * @throws MissingHeaderException
	 * @throws MissingQueryParameterException
	 */
	public static function validateRequest(Request $request, array $routeDefinition)
	{

		//validate headers
		foreach ($routeDefinition["method"]->getHeaders() as $namedParameter) {

			if ($namedParameter->isRequired() === true) {
				// slim converting header key to first upper, @todo refactor in better way
				$testKey = strtolower($namedParameter->getKey());
				$lowerKeys = array_map('strtolower', $request->headers->keys());
				if (!in_array($testKey, $lowerKeys, true)) {
					$message = array();
					$message['missing_header'][$namedParameter->getKey()] = $namedParameter->getDescription();
					throw new MissingHeaderException(json_encode($message));
				}
			}
		}

		// validate query parameters
		foreach ($routeDefinition["method"]->getQueryParameters() as $namedParameter) {
			if ($namedParameter->isRequired() === true) {
				if (!in_array($namedParameter->getKey(), array_keys($request->params()), true)) {
					$message = array();
					$message['missing_parameter'][$namedParameter->getKey()] = $namedParameter->getDescription();
					throw new MissingQueryParameterException(json_encode($message));
				}
			}
		}

		// validate body
		$schema = null;
		try {
			$schema = $routeDefinition["method"]->getBodyByType("application/json")->getSchema();
		} catch (Exception $e) {
		}

		if ($schema !== null) {

			if ($schema->getJsonObject()->required) {
				if ($request->getBody() == "") {
					$message = array();
					$message["missing_body"]["schema"] = json_decode($schema->__toString());
					throw new MissingBodyException(json_encode($message));
				}
			}
		}

	}


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