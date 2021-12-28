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

namespace Phact\Tests\Cases\Controller;

use InvalidArgumentException;
use Modules\Test\Controllers\TestController;
use Phact\Controller\Controller;
use Phact\Main\Phact;
use Phact\Request\HttpRequest;
use Phact\Router\Router;
use Phact\Tests\Templates\AppTest;

/**
 * Class ControllerTest
 * @package Phact\Tests\Cases
 */
class ControllerTest extends AppTest
{
    public function testSimple()
    {
        $this->expectOutputString('test');
        Phact::app()->handleMatch([
            'target' => [TestController::class, 'test']
        ]);
    }

    public function testMatchSimple()
    {
        $this->expectOutputString('test');

        $router = Phact::app()->router;
        $matches = $router->match('/test_route', 'GET');
        $match = $matches[0];

        Phact::app()->handleMatch($match);
    }

    public function _testParams()
    {
        $this->expectOutputString('Name: params_test');
        $controller = new TestController(new HttpRequest());
        $controller->run('testParam', ['name' => 'params_test']);
    }

    public function testMatchParams()
    {
        $this->expectOutputString('Name: params_test');

        $router = Phact::app()->router;
        $matches = $router->match('/test_route/params_test', 'GET');
        $match = $matches[0];

        $controllerClass = $match['target'][0];
        $action = $match['target'][1];

        $this->assertEquals(TestController::class, $controllerClass);
        $this->assertEquals($action, 'testParam');

        Phact::app()->handleMatch($match);
    }

    /**
     */
    public function testInvalidParams()
    {
        $this->expectException(\Phact\Exceptions\InvalidAttributeException::class);
        Phact::app()->handleMatch([
            'target' => [TestController::class, 'unknownAction']
        ]);
    }
}