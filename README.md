# Router

[![Build Status](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/badges/build.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/miBadger/miBadger.Router/?branch=master)

The Router Component.

## Example

```php
<?php

use miBadger/Router/Router;

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
