<?php

/**
 * This file is part of the miBadger package.
 *
 * @author Michael Webbers <michael@webbers.io>
 * @license http://opensource.org/licenses/Apache-2.0 Apache v2 License
 * @version 1.0.0
 */

namespace miBadger\Router;

/**
 * The router test class.
 *
 * @since 1.0.0
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
	/** @var callable The callable. */
	private $callable;

	/** @var array The routes. */
	private $routes;

	/** @var Router The router. */
	private $router;

	public function setUp()
	{
		$this->callable = 'miBadger\Router\routerMethodTest';
		$this->routes = ['GET' => ['/path/' => [$this->callable, []]]];
		$this->router = new Router('', $this->routes);
	}

	public function testGetBasePath()
	{
		$this->assertEquals('', $this->router->getBasePath());
	}

	/**
	 * @depends testGetBasePath
	 */
	public function testSetBasePath()
	{
		$this->assertEquals($this->router, $this->router->setBasePath('/base'));
		$this->assertEquals('/base', $this->router->getBasePath());
	}

	public function testGetIterator()
	{
		$this->assertEquals(new \RecursiveArrayIterator($this->routes), $this->router->getIterator());
	}

	public function testCount()
	{
		$this->assertEquals(1, $this->router->count());
	}

	public function testIsEmpty()
	{
		$this->assertFalse($this->router->isEmpty());
	}

	public function testContains()
	{
		$this->assertTrue($this->router->contains('GET', '/path/'));
	}

	public function testContainsCallable()
	{
		$this->assertTrue($this->router->containsCallable($this->callable));
		$this->assertFalse($this->router->containsCallable(function() { }));
	}

	public function testGet()
	{
		$this->assertEquals([$this->callable, []], $this->router->get('GET', '/path/'));
	}

	public function testSet()
	{
		$this->assertEquals($this->router, $this->router->set('GET', '/path2/', $this->callable, ['key' => 'value']));
		$this->assertEquals([$this->callable, ['key' => 'value']], $this->router->get('GET', '/path2/'));
	}

	public function testRemove()
	{
		$this->assertNull($this->router->remove('GET', '/path/'));
		$this->assertFalse($this->router->contains('GET', '/path/'));
	}

	public function testClear()
	{
		$this->assertNull($this->router->clear());
		$this->assertTrue($this->router->isEmpty());
	}

	public function testResolveMethod()
	{
		$this->assertEquals('method', $this->router->resolve('GET', '/path/'));
	}

	public function testResolveClassMethod()
	{
		$this->router->set('GET', '/class/', ['miBadger\Router\RouterClassTest', 'method']);
		$this->assertEquals('class method', $this->router->resolve('GET', '/class/'));
	}

	public function testResolveClosure()
	{
		$this->router->set('GET', '/closure/', function () { return 'closure'; });
		$this->assertEquals('closure', $this->router->resolve('GET', '/closure/'));
	}

	public function testResolveBasePath()
	{
		$this->router->setBasePath('/base');
		$this->assertEquals('method', $this->router->resolve('GET', '/base/path/'));
	}

	/**
	 * @expectedException miBadger\Http\ServerResponseException
	 * @expectedExceptionMessage Not Found
	 * @expectedExceptionCode 404
	 */
	public function testResolveNotFound()
	{
		$this->router->resolve();
	}
}

class RouterClassTest
{
	public static function method()
	{
		return 'class method';
	}
}

function routerMethodTest()
{
	return 'method';
}
