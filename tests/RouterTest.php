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
class RouterTest extends TestCase
{
	/** @var callable The callable. */
	private $callable;

	/** @var Router The router. */
	private $router;

	public function setUp(): void
	{
		$this->router = new Router('');

		$this->callable = 'miBadger\Router\routerMethodTest';
		$this->router->set(['GET'], '/path/', $this->callable);
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
		$entry = (object)['route' => '/path/', 'pattern' => '|^/path/$|', 'callable' => $this->callable];
		$this->assertEquals(new \RecursiveArrayIterator(['GET' => ['|^/path/$|' => $entry]]), $this->router->getIterator());
	}

	public function testCount()
	{
		$this->assertEquals(1, $this->router->count());
		$this->router->remove('GET', '/path/');
		$this->assertEquals(0, $this->router->count());
	}

	public function testIsEmpty()
	{
		$this->assertFalse($this->router->isEmpty());
		$this->router->remove('GET', '/path/');
		$this->assertTrue($this->router->isEmpty());
	}

	public function testContains()
	{
		$this->assertTrue($this->router->contains('GET', '/path/'));
		$this->router->set(['GET'], '/foo/', function(){});
		$this->assertFalse($this->router->contains('GET', '/bar/'));
	}

	public function testContainsCallable()
	{
		$this->assertTrue($this->router->containsCallable($this->callable));
		$this->assertFalse($this->router->containsCallable(function() { }));
	}

	public function testGet()
	{
		$this->assertEquals($this->callable, $this->router->get('GET', '/path/'));
		$this->assertNull($this->router->get('PUT', ''));

		$this->router->set(['GET'], '/foo/', function(){});
		$this->assertNull($this->router->get('GET', '/bar/'));
	}

	public function testSet()
	{
		$this->assertEquals($this->router, $this->router->set(['GET'], '/path2/', $this->callable));
		$this->assertEquals($this->callable, $this->router->get('GET', '/path2/'));
	}

	public function testRemove()
	{
		$this->assertNull($this->router->remove('GET', '/path/'));
		$this->assertFalse($this->router->contains('GET', '/path/'));

		$this->assertNull($this->router->remove('CUSTOM', '/path/'));
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

	public function testAddMultipleMethods()
	{
		$this->router->set(['GET', 'PUT', 'CUSTOM'], '/test/', function(){ return 'ok'; });

		$this->assertEquals('ok', $this->router->resolve('GET', '/test/'));
		$this->assertEquals('ok', $this->router->resolve('PUT', '/test/'));
		$this->assertEquals('ok', $this->router->resolve('CUSTOM', '/test/'));
	}

	public function testWildcardPath()
	{
		$this->router->set(['GET'], '/first/{one}/second-{two}-third/', function($one, $two){ return [$one, $two]; });
		$this->assertEquals(['test', '12345'], $this->router->resolve('GET', '/first/test/second-12345-third/'));
		$this->assertEquals(['----', '_'], $this->router->resolve('GET', '/first/----/second-_-third/'));

		$this->router->set(['GET'], '{name}', function($name){ return $name; });
		$this->assertEquals('foo', $this->router->resolve('GET', 'foo'));
		$this->assertEquals('bar-baz', $this->router->resolve('GET', 'bar-baz'));
	}

	public function testOverwriteRoute()
	{
		$this->router->set(['GET'], '/test/', function(){ return 'test'; });
		$this->assertEquals($this->router->get('GET', '/test/')(), 'test');

		$this->router->set(['GET'], '/test/', function(){ return 'overwrite'; });
		$this->assertEquals($this->router->get('GET', '/test/')(), 'overwrite');

		$this->router->set(['GET'], '/{test}/', function(){ return 'test'; });
		$this->router->set(['GET'], '/{test2}/', function(){ return 'overwrite'; });
		$this->assertEquals($this->router->get('GET', '/{test1}/')(), 'overwrite');
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
		
		$this->router->set(['GET'], '{name}', function($name){ return $name; });
		$this->router->resolve('GET', '/foo/');
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
