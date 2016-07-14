# Router

The router class.

## Example(s)

```php
<?php

use miBadger\Router\Router;

/**
 * Construct a Router object with the given routers.
 */
$router = new Router($basePath = '', $routes = [], $parameters = []);

/**
 * Returns the base path.
 */
$router->getBasePath();

/**
 * Set the base path.
 */
$router->setBasePath($basePath);

/**
 * {@inheritdoc}
 */
$router->getIterator();

/**
 * Returns the number of key-value mappings in the router map.
 */
$router->count();

/**
 * Returns true if the router map contains no key-value mappings.
 */
$router->isEmpty();

/**
 * Returns true if the router map contains a mapping for the specified method & route.
 */
$router->contains($method, $route);

/**
 * Returns true if the router map maps one or more routes to the specified callable.
 */
$router->containsCallable(callable $callable);

/**
 * Returns the callable to which the specified route is mapped, or null if the router map contains no mapping for the route.
 */
$router->get($method, $route);

/**
 * Associates the specified callable with the specified method & route in the route map.
 */
$router->set($method, $route, callable $callable, array $parameters = []);

/**
 * Removes the mapping for the specified route from the router map if present.
 */
$router->remove($method, $route);

/**
 * Removes all of the mappings from the router map.
 */
$router->clear();

/**
 * Returns the result of the given route's callable.
 */
$router->resolve($method = null, $route = null);
```
