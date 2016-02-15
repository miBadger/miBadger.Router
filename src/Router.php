<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 * @version 1.0.0
 */

namespace miBadger\Router;

use miBadger\Http\ServerRequest;
use miBadger\Http\ServerResponse;
use miBadger\Http\ServerResponseException;

/**
 * The router class.
 *
 * @since 1.0.0
 */
class Router implements \IteratorAggregate
{
	/** @var string The base path. */
	private $basePath;

	/** @var array The routes. */
	private $routes;

	/** @var array The parameters. */
	private $parameters;

	/**
	 * Construct a Router object with the given routers.
	 *
	 * @param string $basePath = ''
	 * @param array $routes = []
	 * @param array $parameters = []
	 */
	public function __construct($basePath = '', $routes = [], $parameters = [])
	{
		$this->basePath = $basePath;
		$this->routes = $routes;
		$this->parameters = $parameters;
	}

	/**
	 * Returns the base path.
	 *
	 * @return string the base path.
	 */
	public function getBasePath()
	{
		return $this->basePath;
	}

	/**
	 * Set the base path.
	 *
	 * @param string $basePath
	 * @return $this
	 */
	public function setBasePath($basePath)
	{
		$this->basePath = $basePath;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIterator()
	{
		return new \RecursiveArrayIterator($this->routes);
	}

	/**
	 * Returns the number of key-value mappings in the router map.
	 *
	 * @return int the number of key-value mappings in the router map.
	 */
	public function count()
	{
		$result = 0;

		foreach ($this->routes as $routes) {
			$result += count($routes);
		}

		return $result;
	}

	/**
	 * Returns true if the router map contains no key-value mappings.
	 *
	 * @return bool true if the router map contains no key-value mappings.
	 */
	public function isEmpty()
	{
		return empty($this->routes);
	}

	/**
	 * Returns true if the router map contains a mapping for the specified method & route.
	 *
	 * @param string $method
	 * @param string $route
	 * @return bool true if the static route map contains a mapping for the specified method & route.
	 */
	public function contains($method, $route)
	{
		return isset($this->routes[$method][$route]);
	}

	/**
	 * Returns true if the router map maps one or more routes to the specified callable.
	 *
	 * @param callable $callable
	 * @return bool true if the router map maps one or more routes to the specified callable.
	 */
	public function containsCallable(callable $callable)
	{
		foreach ($this->routes as $route) {
			foreach ($route as $result) {
				if ($result[0] === $callable) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns the callable to which the specified route is mapped, or null if the router map contains no mapping for the route.
	 *
	 * @param string $method
	 * @param string $route
	 * @return array the callable to which the specified route is mapped, or null if the router map contains no mapping for the route.
	 */
	public function get($method, $route)
	{
		return $this->contains($method, $route) ? $this->routes[$method][$route] : null;
	}

	/**
	 * Associates the specified callable with the specified method & route in the route map.
	 *
	 * @param string $method
	 * @param string $route
	 * @param callable $callable
	 * @param array $parameters = []
	 * @return $this
	 */
	public function set($method, $route, callable $callable, array $parameters = [])
	{
		$this->routes[$method][$route] = [$callable, $parameters + $this->parameters];

		return $this;
	}

	/**
	 * Removes the mapping for the specified route from the router map if present.
	 *
	 * @param string $method
	 * @param string $route
	 * @return null
	 */
	public function remove($method, $route)
	{
		unset($this->routes[$method][$route]);
	}

	/**
	 * Removes all of the mappings from the router map.
	 *
	 * @return null
	 */
	public function clear()
	{
		$this->routes = [];
	}

	/**
	 * Returns the result of the given route's callable.
	 *
	 * @param string|null $method = new ServerRequest()->getMethod()
	 * @param string|null $route = new ServerRequest()->getUri()->getPath()
	 * @return mixed the result of the given route's callable.
	 * @throws ServerResponseException
	 */
	public function resolve($method = null, $route = null)
	{
		$serverRequest = new ServerRequest();

		if ($method === null) {
			$method = $serverRequest->getMethod();
		}

		if ($route === null) {
			$route = $serverRequest->getUri()->getPath();
		}

		if ($this->basePath !== '' && strpos($route, $this->basePath, 0) === 0) {
			$route = substr($route, strlen($this->basePath));
		}

		return $this->callCallable($this->getCallable($method, $route));
	}

	/**
	 * Returns the callable to which the specied method and route are mapped.
	 *
	 * @param string $method
	 * @param string $route
	 * @return array the callable to which the specied method and route are mapped.
	 * @throws ServerResponseException
	 */
	private function getCallable($method, $route)
	{
		if ($this->contains($method, $route)) {
			return $this->get($method, $route);
		}

		throw new ServerResponseException(new ServerResponse(404));
	}

	/**
	 * Returns the result of the callable.
	 *
	 * @param array $callable
	 * @return mixed the result the callable.
	 */
	private function callCallable($callable)
	{
		return call_user_func_array($callable[0], $callable[1]);
	}
}