<?php

namespace RamlServer;

use Raml\Method;
use Raml\Schema\Definition\JsonSchemaDefinition;
use Slim\Http\Request;

class RequestValidator
{

	/**
	 * Run validation on headers, query parameters, and body against the route definition,
	 * verifying that required items exist. It returns nothing, but throws Exceptions if
	 * a validation does not pass
	 *
	 * @param Request $request
	 * @param Method $method
	 * @throws MissingBodyException
	 * @throws MissingHeaderException
	 * @throws MissingQueryParameterException
	 * @throws \Exception
	 */
	public static function validate(Request $request, Method $method)
	{
		self::validateHeaders($request, $method);
		self::validateQueryParameters($request, $method);
		self::validateBody($request, $method);
	}


	/**
	 * @param Request $request
	 * @param Method $method
	 * @throws MissingHeaderException
	 */
	public static function validateHeaders(Request $request, Method $method)
	{
		foreach ($method->getHeaders() as $namedParameter) {
			if ($namedParameter->isRequired() === true) {
				// slim converting header key to first upper, @todo refactor in better way
				$testKey = strtolower($namedParameter->getKey());
				$lowerKeys = array_map('strtolower', $request->headers->keys());
				if (!in_array($testKey, $lowerKeys, true)) {
					$key = $namedParameter->getKey();
					throw new MissingHeaderException(
						"Missing required header `$key`"
					);
				}
			}
		}
	}


	/**
	 * @param Request $request
	 * @param Method $method
	 * @throws MissingQueryParameterException
	 */
	public static function validateQueryParameters(Request $request, Method $method)
	{
		foreach ($method->getQueryParameters() as $namedParameter) {
			if ($namedParameter->isRequired() === true) {
				if (!array_key_exists($namedParameter->getKey(), $request->params())) {
					$key = $namedParameter->getKey();
					throw new MissingQueryParameterException(
						"Missing required query parameter `$key`"
					);
				}
			}
		}
	}


	/**
	 * @param Request $request
	 * @param Method $method
	 * @throws MissingBodyException
	 * @throws \Exception
	 */
	public static function validateBody(Request $request, Method $method)
	{
		/** @var JsonSchemaDefinition $schema */
		$schema = null;

		$bodies = $method->getBodies();
		if (isset($bodies['application/json'])) {
			$schema = $method->getBodyByType('application/json')->getSchema();
		}

		if ($schema !== null) {
			if ($schema->getJsonObject()->required) {
				if ($request->getBody() === '') {
					$message = array();
					$message['missing_body']['schema'] = json_decode((string) $schema);
					throw new MissingBodyException(json_encode($message));
				}
			}
		}
	}
}