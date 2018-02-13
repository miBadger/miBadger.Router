<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
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

	/**
	 * Construct a Router object with the given routers.
	 *
	 * @param string $basePath = ''
	 */
	public function __construct($basePath = '')
	{
		$this->basePath = $basePath;
		$this->routes = [];
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
		return $this->get($method, $route) !== null;
	}

	/**
	 * Returns true if the router map maps one or more routes to the specified callable.
	 *
	 * @param callable $callable
	 * @return bool true if the router map maps one or more routes to the specified callable.
	 */
	public function containsCallable(callable $callable)
	{
		foreach ($this->routes as $method) {
			foreach ($method as $entry)
			{
				if ($entry->callable === $callable) {
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
	 * @return callable the callable to which the specified route is mapped, or null if the router map contains no mapping for the route.
	 */
	public function get($method, $route)
	{
		if (!array_key_exists($method, $this->routes)) {
			return null;
		}

		$pattern = $this->createPattern($route);

		if (array_key_exists($pattern, $this->routes[$method])) {
			return $this->routes[$method][$pattern]->callable;
		}

		return null;
	}

	/**
	 * Associates the specified callable with the specified method & route in the route map.
	 *
	 * @param string|array $methods
	 * @param string $route
	 * @param callable $callable
	 * @return $this
	 */
	public function set($methods, $route, callable $callable)
	{
		if (is_string($methods)) {
			$methods = [$methods];
		}

		foreach ($methods as $method) {
			$this->add($method, $route, $callable);
		}

		return $this;
	}

	/**
	 * Adds a method & route to the to the route map.
	 *
	 * @param string $method
	 * @param string $route
	 * @param callable $callable
	 */
	private function add(string $method, string $route, callable $callable)
	{
		$pattern = $this->createPattern($route);

		$entry = (object)[
			'route' => $route,
			'pattern' => $pattern,
			'callable' => $callable
		];

		if (!array_key_exists($method, $this->routes)) {
			$this->routes[$method] = [];
		}

		$this->routes[$method][$pattern] = $entry;
	}

	/**
	 * Creates a regex-enabled pattern from the route
	 *
	 * @param string $route
	 * @return string the pattern string.
	 */
	private function createPattern(string $route)
	{
		return '|^' . preg_replace('|\{[^\}]+\}|', '([^\/]+)', $route) . '$|';
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
		if (!array_key_exists($method, $this->routes)) {
			return;
		}

		$pattern = $this->createPattern($route);

		if (array_key_exists($pattern, $this->routes[$method])) {
			unset($this->routes[$method][$pattern]);
		}

		if (count($this->routes[$method]) == 0) {
			unset($this->routes[$method]);
		}
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
	 * @return array the callable to which the specied method and route are mapped and the route matches.
	 * @throws ServerResponseException
	 */
	private function getCallable($method, $route)
	{
		if (!array_key_exists($method, $this->routes)) {
			throw new ServerResponseException(new ServerResponse(404));
		}

		foreach ($this->routes[$method] as $entry) {
			if (preg_match($entry->pattern, $route, $matches) > 0) {
				array_shift($matches);
				return [$entry->callable, $matches];
			}
		}

		throw new ServerResponseException(new ServerResponse(404));
	}

	/**
	 * Returns the result of the callable.
	 *
	 * @param array the callable and the route matches.
	 * @return mixed the result the callable.
	 */
	private function callCallable($callable)
	{
		return call_user_func_array($callable[0], $callable[1]);
	}
}
