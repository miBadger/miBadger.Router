<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 */

namespace miBadger\Router;

use PHPUnit\Framework\TestCase;

/**
 * The router test class.
 *
 * @since 1.0.0
 */
class AccessRouterTest extends TestCase
{
	/** @var callable The callable. */
	private $callable;

	/** @var Router The router. */
	private $router;

	public function setUp(): void
	{
		$this->user = new User();
		$this->router = new AccessRouter($this->user, '');

		$this->callable = 'miBadger\Router\accessRouterMethodTest';
		$this->router->add('GET', '/path/', null, $this->callable);
	}

	public function testResolveMethod()
	{
		$this->assertEquals('method', $this->router->resolve('GET', '/path/'));
	}

	public function testResolvePermission()
	{
		// user + correct permission set
		$this->router->add('GET', '/read/', Permission::READ_ACCESS, function() {
			return 'access granted!';
		});
		$this->assertEquals('access granted!', $this->router->resolve('GET', '/read/'));
	}

	public function testAccessDeniedOnResolve()
	{
		$this->expectException(\miBadger\Http\ServerResponseException::class);
		$this->expectExceptionCode(403);

		// user + incorrect permission set
		$this->router->add('GET', '/delete/', Permission::DELETE_ACCESS, function () {
			return 'access granted';
		});
		$this->router->resolve('GET', '/delete/');
	}

	public function testAccessDeniedOnNoUser()
	{
		$this->expectException(\miBadger\Http\ServerResponseException::class);
		$this->expectExceptionCode(403);		
		// null user + permission set
		$noUserRouter = new AccessRouter(null, '');
		$noUserRouter->add('GET', '/read/', Permission::READ_ACCESS, function() {
			return 'access granted!';
		});
		$noUserRouter->resolve('GET', '/read/');
	}

	public function testResolveNoPermission()
	{
		// User + null permission on route
		$this->assertEquals('method', $this->router->resolve('GET', '/path/'));
	}

	public function testResolveNoUserNoPermission()
	{
		// null user + null permission on route
		$noUserRouter = new AccessRouter(null, '');
		$noUserRouter->add('GET', '/path/', null, $this->callable);
		$this->assertEquals('method', $noUserRouter->resolve('GET', '/path/'));
	}


	public function testGetPermissionMap()
	{
		$this->router->add('GET', '/foo/{id}/', Permission::READ_ACCESS, function ($id) {
			return $id;
		});

		$this->assertEquals(['/path/' => null, '/foo/%s/' => [Permission::READ_ACCESS]], $this->router->makeGETRoutePermissionsMap());
	}

	public function testResolveNotFound()
	{
		$this->expectException(\miBadger\Http\ServerResponseException::class);
		$this->expectExceptionMessage("Not Found");
		$this->expectExceptionCode(404);

		$this->router->resolve();
	}

	public function testResolveWildcardNotFound()
	{
		$this->expectException(\miBadger\Http\ServerResponseException::class);
		$this->expectExceptionMessage("Not Found");
		$this->expectExceptionCode(404);

		$this->router->add('GET', '{name}', null, function($name){ return $name; });
		$this->router->resolve('GET', '/foo/');
	}	
}

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

function accessRouterMethodTest()
{
	return 'method';
}
