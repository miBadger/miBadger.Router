# Router

The router class is used to simplify routing.

## Example(s)

```php
// Create a new router.
$router = new Router();

// Create a method route.
$router->set('GET', '/method/', 'method');

// Create a class method route.
$router->set('GET', '/class/', ['Class', 'method']);

// Create a closure route.
$router->set('GET', '/closure/', function() { return 'result'; });

// Create a wildcard route.
$router->set('GET', '/*/', function() { return 'result'; });

// Resolve route
try {
	echo $router->resolve();
} catch (ServerResponseException $e) {
	$e->getServerResponse()->send();
} catch (Exception $e) {
	echo 'Internal server error';
}
```
