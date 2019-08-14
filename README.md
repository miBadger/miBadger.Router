# Router

[![Build Status](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/badges/build.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/68ea797b-bc99-42df-8c97-1fa99b90fc72/mini.png)](https://insight.sensiolabs.com/projects/68ea797b-bc99-42df-8c97-1fa99b90fc72)

The Router Component.

## Example

```php
<?php

use miBadger\Router\Router;

/**
 * Create a new router.
 */
$router = new Router();

/**
 * Create a method route.
 */
$router->set('GET', '/method/', 'method');

/**
 * Create a class method route.
 */
$router->set('GET', '/class/', ['Class', 'method']);

/**
 * Create a closure route.
 */
$router->set('GET', '/closure/', function() {
	return 'result';
});

/**
 * Create a wildcard route.
 */
$router->set('GET', '/test/{wildcard}/', function($wildcard) {
	return $wildcard;
});

/**
 * Add multiple methods for a route.
 */
$router->set(['GET', 'POST', 'TEST'], '/route/', function() {
	return 'result';
});

/**
 * Resolve
 */
$router->resolve();
```

## Access Router Example
The access router behaves very similarly to the normal router, except that it expects an object in the constructor implementing the "PermissionCheckable" interface (that for example represents the logged-in user), or null (in case of an anauthenticated entity).
The ```$router->add``` method now requires an extra parameter that specifies the permission required to access this route. The PermissionCheckable interface will then determine whether this conditions is met during the resolving of the route.

```php
class Permission
{
	const READ_ACCESS = "READ_ACCESS";
	const WRITE_ACCESS = "WRITE_ACCESS";
	const DELETE_ACCESS = "DELETE_ACCESS";
}

class User implements PermissionCheckable
{
	public function hasPermission($permission)
	{
		$permissions = [Permission::READ_ACCESS, Permission::WRITE_ACCESS];
		return in_array($permission, $permissions);
	}
}

$router = new AccessRouter(new User(), '');


$router->add('GET', '/read/', Permission::READ_ACCESS, function() {
	return 'result';
});


$router->resolve();
```
