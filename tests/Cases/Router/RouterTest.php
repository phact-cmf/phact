<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 09/04/16 11:26
 */

namespace Phact\Tests\Cases\Router;

use InvalidArgumentException;
use Phact\Helpers\Paths;
use Phact\Main\Phact;
use Phact\Router\Router;
use Phact\Tests\Templates\AppTest;

class RouterTest extends AppTest
{
    public function testCollectFromFile()
    {
        $router = Phact::app()->getContainer()->construct(Router::class, ['base.config.routes']);
        $this->assertEquals([[
                'GET|POST',
                '/test_route',
                [
                    'Modules\Test\Controllers\TestController',
                    'test'
                ],
                'test:test'
            ],
            [
                'GET|POST',
                '/test_route/{:name}',
                [
                    'Modules\Test\Controllers\TestController',
                    'testParam'
                ],
                'test:test_param'
            ]
        ], $router->getRoutes());

        $this->assertEquals('/test_route', $router->url('test:test'));
        $this->assertEquals([[
            'target' => [
                'Modules\Test\Controllers\TestController',
                'test'
            ],
            'params' => [],
            'name' => 'test:test'
        ]], $router->match('/test_route', 'GET'));
        $this->assertEquals('/test_route', $router->url('test:test'));
    }

    public function testParameterRoutes()
    {
        $router = new Router();
        $router->collect([
            [
                'route' => '/test1/{[0-9]+:id}',
                'target' => 'target',
                'name' => 'first-route'
            ],
            [
                'route' => '/test2/{slug:name}',
                'target' => 'target',
                'name' => 'second-route'
            ],
            [
                'route' => '/test3/{:name}',
                'target' => 'target',
                'name' => 'third-route'
            ]
        ]);

        $this->assertEquals([[
            'target' => 'target',
            'params' => [
                'id' => '0102'
            ],
            'name' => 'first-route'
        ]], $router->match('/test1/0102'));

        $this->assertEquals("/test1/123", $router->url('first-route', ['id' => 123]));
        $this->assertEquals("/test1/321", $router->url('first-route', [321]));

        $this->assertEquals([[
            'target' => 'target',
            'params' => [
                'name' => 'amazing_route'
            ],
            'name' => 'second-route'
        ]], $router->match('/test2/amazing_route'));

        $this->assertEquals("/test2/amazing_route", $router->url('second-route', ['name' => 'amazing_route']));
        $this->assertEquals("/test2/amazing_route", $router->url('second-route', ['amazing_route']));

        $this->assertEquals([[
            'target' => 'target',
            'params' => [
                'name' => 'amazing_route'
            ],
            'name' => 'third-route'
        ]], $router->match('/test3/amazing_route'));

        $this->assertEquals("/test3/amazing_route", $router->url('third-route', ['name' => 'amazing_route']));
        $this->assertEquals("/test3/amazing_route", $router->url('third-route', ['amazing_route']));

        return $router;
    }

    /**
     * @depends testParameterRoutes
     * @param $router Router
     */
    public function testInvalidArgument($router)
    {
        $this->expectException(InvalidArgumentException::class);
        $router->url('first-route');
    }

    public function testIndex()
    {
        $router = new Router();
        $router->collect([
            [
                'route' => '',
                'target' => 'target',
                'name' => 'first-route'
            ]
        ]);

        $this->assertEquals([[
            'target' => 'target',
            'params' => [],
            'name' => 'first-route'
        ]], $router->match('/'));

        $this->assertEquals([[
            'target' => 'target',
            'params' => [],
            'name' => 'first-route'
        ]], $router->match(''));
    }
}