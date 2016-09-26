<?php

namespace RamlServer;

use Exception;
use Raml\Method;
use Raml\Schema\Definition\JsonSchemaDefinition;
use Slim\Http\Request;

class RequestValidator
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
	public static function validate(Request $request, array $routeDefinition)
	{
		self::validateHeaders($request, $routeDefinition);
		self::validateQueryParameters($request, $routeDefinition);
		self::validateBody($request, $routeDefinition);
	}


	/**
	 * @param Request $request
	 * @param array $routeDefinition
	 * @throws MissingHeaderException
	 */
	public static function validateHeaders(Request $request, array $routeDefinition)
	{
		/** @var Method $method */
		$method = $routeDefinition['method'];

		foreach ($method->getHeaders() as $namedParameter) {
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
	}


	/**
	 * @param Request $request
	 * @param array $routeDefinition
	 * @throws MissingQueryParameterException
	 */
	protected static function validateQueryParameters(Request $request, array $routeDefinition)
	{
		/** @var Method $method */
		$method = $routeDefinition['method'];
		foreach ($method->getQueryParameters() as $namedParameter) {
			if ($namedParameter->isRequired() === true) {
				if (array_key_exists($namedParameter->getKey(), $request->params())) {
					$message = array();
					$message['missing_parameter'][$namedParameter->getKey()] = $namedParameter->getDescription();
					throw new MissingQueryParameterException(json_encode($message));
				}
			}
		}
	}


	/**
	 * @param Request $request
	 * @param array $routeDefinition
	 * @throws MissingBodyException
	 */
	protected static function validateBody(Request $request, array $routeDefinition)
	{
		/** @var JsonSchemaDefinition $schema */
		$schema = null;
		try {
			$schema = $routeDefinition['method']->getBodyByType('application/json')->getSchema();
		} catch (Exception $e) {
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