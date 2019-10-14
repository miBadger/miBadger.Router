<?php

namespace miBadger\Router;

use miBadger\Http\ServerRequest;
use miBadger\Http\ServerResponse;
use miBadger\Http\ServerResponseException;

class AccessRouter
{
	private $routes; // $routes[method] = (route, pattern, callable, permission)

	private $basePath;

	private $user;

	public function __construct(PermissionCheckable $user = null, $basePath = '')
	{
		$this->user = $user;
		$this->routes = [];
		$this->basePath = $basePath;
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

	public function add(string $method, string $route, $permission, callable $callable)
	{
		$entry = (object)[
			'route' => $route,
			'pattern' => $this->createPattern($route),
			'permission' => $permission,
			'callable' => $callable
		];

		if (!array_key_exists($method, $this->routes)) {
			$this->routes[$method] = [];
		}

		$this->routes[$method][] = $entry;
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
	public function remove(string $method, string $route, $permission)
	{
		if (!array_key_exists($method, $this->routes)) {
			return;
		}

		foreach ($this->routes[$method] as $key => $entry) {
			if ($entry->route == $route && $entry->permission == $permission) {
				unset($this->routes[$method][$key]);
			}
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
	 * Returns the result of the callable.
	 *
	 * @param array the callable and the route matches.
	 * @return mixed the result the callable.
	 */
	private function callCallable($callable)
	{
		return call_user_func_array($callable[0], $callable[1]);
	}

	public function getMatchingRoutes($method, $route)
	{
		if (!array_key_exists($method, $this->routes)) {
			throw new ServerResponseException(new ServerResponse(404));
		}

		$matchedRoutes = [];

		foreach ($this->routes[$method] as $entry) {
			if (preg_match($entry->pattern, $route, $matches) > 0) {
				// At this point the route is matched
				array_shift($matches);
				$matchedRoutes[] = [$route, $entry, $matches];
			}
		}

		if (count($matchedRoutes) == 0) {
			throw new ServerResponseException(new ServerResponse(404));		
		}

		return $matchedRoutes;
	}

	protected function getCallable($method, $route)
	{
		// Check if permissions are alright
		$matchedRoutes = $this->getMatchingRoutes($method, $route);

		foreach ($matchedRoutes as $match) {
			$route = $match[0];
			$entry = $match[1];
			$matches = $match[2];

			if ($entry->permission === null 
				|| ($entry->permission !== null && $this->user !== null && $this->user->hasPermission($entry->permission)))
			{
				return [$entry->callable, $matches];
			}

		}

		throw new ServerResponseException(new ServerResponse(403));
	}

	/**
	 * Returns the cleaned ({token} => %s) permission map for the router 
	 * (associative map from route to the set of permissions that have access to that route), 
	 * 		null if a route has wildcard permissions
	 */
	public function makeGETRoutePermissionsMap()
	{
		$outputMap = [];

		foreach ($this->routes["GET"] as $entry) {

			// Extract clean route by replacing every slug occurrence with %s
			$slugFreeRoute = preg_replace('/({.*?})/', '%s', $entry->route);

			if ($entry->permission === null) {
				$outputMap[$slugFreeRoute] = null;	
				continue;
			}

			if (!isset($outputMap[$slugFreeRoute])) {
				$outputMap[$slugFreeRoute] = [];
			}

			$outputMap[$slugFreeRoute][] = $entry->permission;
		}

		return $outputMap;
	}
}