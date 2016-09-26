<?php

namespace RamlServer;

use InvalidArgumentException;
use Nette\Caching\Cache;
use Nette\Utils\Finder;
use Raml\ApiDefinition;
use Raml\Method;
use Raml\Parser;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Slim;


/**
 * Class ZeroRouter
 *
 * Provides very simple routing facility before normal router
 * will take place. It can be used as dispatcher among RamlServerRouter and
 * normal application router.
 *
 * Usage:
 * $uri = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'
 * $zeroRouter = new ZeroRouter($uri, 'http://test-server/api')
 *
 * @package RamlServer
 */
final class ZeroRouter
{

	/** @var string */
	private $uri;

	/** @var string */
	private $apiUri;

	/** @var bool */
	private $isApi = null;

	/** @var bool */
	private $isRaml = null;

	/** @var string */
	private $apiName;

	/** @var string */
	private $version;

	/** @var array */
	private $options;

	/** @var  string */
	private $ramlFile;

	/** @var Cache */
	private $cache;

	/** @var mixed[] */
	private $factories = [];

	/** @var Slim */
	private $app;


	/**
	 * ZeroRouter constructor.
	 * @param array $options
	 * @param string $uri
	 */
	public function __construct(array $options, $uri)
	{
		$this->options = $options;
		$this->uri = $uri;
		$this->apiUri = $this->getOption('server') . '/' . $this->getOption('apiUriPart');
		$this->ramlUri = $this->getOption('server') . '/' . $this->getOption('ramlUriPart');
		$this->detectApiUrl();
	}


	/**
	 * @param Slim $app
	 */
	public function setSlimApp(Slim $app)
	{
		$this->app = $app;
	}


	/**
	 * @param Cache $cache
	 */
	public function setCache(Cache $cache)
	{
		$this->cache = $cache;
	}


	/**
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->app->container->get('request');
	}


	/**
	 * @return Response
	 */
	public function getResponse()
	{
		return $this->app->container->get('response');
	}


	/**
	 * @return bool
	 */
	public function isApiRequest()
	{
		return $this->isApi;
	}


	/**
	 * @return bool
	 */
	public function isRamlRequest()
	{
		return $this->isRaml;
	}


	public function serveApi()
	{
		$apiDef = $this->getParsedDefinition();
		if ($this->app === null) {
			$this->createSlimApp();
		}
		$this->configureRouter($apiDef);
		$this->app->run();
	}


	/**
	 * @throws RamlRuntimeException
	 */
	public function serveRamlFiles()
	{
		header('Content-Type: text/raml');
		$localPath = $this->getRamlRootDirectory()
			. '/' . $this->getApiName()
			. '/' . $this->getVersion()
			. '/' . $this->getRequestedRamlFile();

		if (!file_exists($localPath)) {
			throw new RamlRuntimeException("File {$localPath} does not exist.");
		}

		if ($this->getRequestedRamlFile() === 'index.raml') {
			$apiUrl = $this->getApiUrl();
			echo preg_replace('/^(baseUri:)\s*(.+)$/m', "\$1 ${apiUrl}", file_get_contents($localPath));
		} else {
			readfile($localPath);
		}
	}


	/**
	 * <http://...server>/<apiUriPath>/<apiName>/users
	 * @return string|null
	 */
	public function getApiName()
	{
		return $this->apiName;
	}


	/**
	 * @return string|null
	 */
	public function getVersion()
	{
		return $this->version;
	}


	/**
	 * @return string
	 */
	public function getApiIndexFile()
	{
		return $this->getApiDirectory() . '/index.raml';
	}


	/**
	 * @return string
	 */
	public function getRequestedRamlFile()
	{
		return $this->ramlFile;
	}


	/**
	 * @param $optionName
	 * @param null $default
	 * @return mixed|null
	 * @internal
	 */
	public function getOption($optionName, $default = null)
	{
		if (array_key_exists($optionName, $this->options)) {
			return $this->options[$optionName];
		}
		if (func_num_args() === 1) {
			throw new InvalidArgumentException(
				"RamlServer: Invalid configuration, key `$optionName` is missing"
			);
		}
		return $default;
	}


	/**
	 * @param IProcessorFactory|callable $processorFactory
	 * @throws RamlRuntimeException
	 */
	public function addProcessor($processorFactory)
	{
		if (!$processorFactory instanceof IProcessorFactory && !is_callable($processorFactory)) {
			throw new RamlRuntimeException('Factory must be callable or implement IProcessorFactory');
		}
		$this->factories[] = $processorFactory;
	}


	/**
	 * @return string
	 */
	private function getRamlRootDirectory()
	{
		return $this->getOption('ramlDir');
	}


	/**
	 * @return string
	 */
	private function getApiDirectory()
	{
		return $this->getRamlRootDirectory() . '/' . $this->getApiName() . '/' . $this->getVersion();
	}


	/**
	 * @return ApiDefinition
	 * @throws RamlRuntimeException
	 */
	private function getParsedDefinition()
	{
		if ($this->cache) {
			$definition = $this->cache->load($this->getApiIndexFile());
			if ($definition === null) {
				$definition = $this->createParsedDefinition();
				$files = array_keys(iterator_to_array(Finder::findFiles('*')->in($this->getApiDirectory())));
				$this->cache->save(
					$this->getApiIndexFile(),
					$definition,
					[Cache::FILES => $files]
				);
			}
		} else {
			$definition = $this->createParsedDefinition();
		}
		return $definition;
	}


	/**
	 * @return ApiDefinition
	 * @throws RamlRuntimeException
	 */
	private function createParsedDefinition()
	{
		$ramlIndexPath = $this->getApiIndexFile();
		if (!file_exists($ramlIndexPath)) {
			throw new RamlRuntimeException("File {$ramlIndexPath} does not exist.");
		}
		$source = file_get_contents($ramlIndexPath);
		$parser = new Parser();
		return $parser->parseFromString($source, $this->getApiDirectory());
	}


	private function detectApiUrl()
	{
		$this->isApi = false;
		$this->isRaml = false;

		if (strpos($this->uri, $this->apiUri) === 0) {
			$part = substr($this->uri, strlen($this->apiUri) + 1);
			$parts = explode('/', $part);

			if ((count($parts) >= 2) && (!empty($parts[0])) && (!empty($parts[1]))) {
				list($apiName, $version) = $parts;

				if (preg_match('/\A[vV]{0,1} [\d]+ (\.[\d]+(\.[\d]+){0,1}){0,1}\Z/mx', $version)) {
					$this->version = $version;
					$this->apiName = $apiName;
					$this->isApi = true;
				}

			}
		} elseif (strpos($this->uri, $this->ramlUri) === 0) {
			$part = substr($this->uri, strlen($this->ramlUri) + 1);
			$parts = explode('/', $part, 3);

			//at least api-name and version must be part of url
			if ((count($parts) >= 3) && (!empty($parts[0])) && (!empty($parts[1]))) {
				list($this->apiName, $this->version, $this->ramlFile) = $parts;
				$this->isRaml = true;
			}
		}
	}


	/**
	 * @return string
	 */
	private function getApiUrl()
	{
		return
			$this->getOption('server')
			. '/' . $this->getOption('apiUriPart')
			. '/' . $this->getApiName()
			. '/' . $this->getVersion();
	}


	/**
	 * @param ApiDefinition $apiDefinition
	 */
	private function configureRouter(ApiDefinition $apiDefinition)
	{
		$apiStarts = $this->getOption('apiUriPart') . '/' . $this->getApiName();

		// This is where a persistence layer ACL check would happen on authentication-related HTTP request items
		$authenticate = function (Slim $app) {
			return function () use ($app) {
				if (false) {
					$app->halt(403, 'Invalid security context');
				}
			};
		};

		if ($this->app === null) {
			$this->app = $this->createSlimApp();
		}

		foreach ($apiDefinition->getResourcesAsUri()->getRoutes() as $route) {

			/** @var Method $method */
			$method = $route['method'];
			$httpMethod = strtolower($method->getType());

			//get,post,...
			$this->app->$httpMethod(

			//route path
			/**
			 * @throws RamlRuntimeException
			 */
				'/' . $apiStarts . '/' . $apiDefinition->getVersion() . $route['path'],

				//authenticate middleware
				$authenticate($this->app),

				//last middleware
				function () use ($route) {

					$request = $this->getRequest();
					$response = $this->getResponse();

					$handled = false;

					foreach ($this->factories as $processorFactory) {
						$processor = $processorFactory instanceof IProcessorFactory ? $processorFactory->create() : $processorFactory();
						if ($handled = $processor->process($this, $request, $response, $route) === true) {
							break;
						}
					}

					if ($handled === false) {
						throw new RamlRuntimeException('No processor handled this API request.');
					}

					// API definitions are assumed to have this Content-Type for all content returned
					$this->app->response->headers->set('Content-Type', 'application/json');
				}
			);

		}
	}


	/**
	 * @return Slim
	 */
	private function createSlimApp()
	{
		$app = new Slim([
			'mode' => 'production',
		]);

		unset($app->container['errorHandler']);

		$app->configureMode('production', function () use ($app) {
			$app->config(array(
				'log.enable' => true,
				'debug' => true
			));
		});

		return $app;
	}


}