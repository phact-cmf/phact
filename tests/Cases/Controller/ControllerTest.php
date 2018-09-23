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

namespace Phact\Tests;

use InvalidArgumentException;
use Modules\Test\Controllers\TestController;
use Phact\Controller\Controller;
use Phact\Main\Phact;
use Phact\Request\HttpRequest;
use Phact\Router\Router;

/**
 * Class ControllerTest
 * @package Phact\Tests
 */
class ControllerTest extends AppTest
{
    public function _testSimple()
    {
        $this->expectOutputString('test');
        $controller = new TestController(new HttpRequest());
        $controller->run('test');
    }

    public function _testMatchSimple()
    {
        $this->expectOutputString('test');

        $router = Phact::app()->router;
        $matches = $router->match('/test_route', 'GET');
        $match = $matches[0];

        $controllerClass = $match['target'][0];
        $action = $match['target'][1];
        $params = $match['params'];

        /** @var Controller $controller */
        $controller = new $controllerClass(new HttpRequest());
        $controller->run($action, $params);
    }

    public function _testParams()
    {
        $this->expectOutputString('Name: params_test');
        $controller = new TestController(new HttpRequest());
        $controller->run('testParam', ['name' => 'params_test']);
    }

    public function _testMatchParams()
    {
        $this->expectOutputString('Name: params_test');

        $router = Phact::app()->router;
        $matches = $router->match('/test_route/params_test', 'GET');
        $match = $matches[0];
        
        $controllerClass = $match['target'][0];
        $action = $match['target'][1];
        $params = $match['params'];

        $this->assertEquals(TestController::class, $controllerClass);
        $this->assertEquals($action, 'testParam');

        /** @var Controller $controller */
        $controller = new $controllerClass(new HttpRequest());
        $controller->run($action, $params);
    }

    /**
     * @expectedException \Phact\Exceptions\InvalidConfigException
     */
    public function _testInvalidParams()
    {
        $controller = new TestController(new HttpRequest());
        $controller->run('testParam', ['id' => 'params_test']);
    }


    /**
     * @expectedException \Phact\Exceptions\InvalidConfigException
     */
    public function _testUnknownAction()
    {
        $controller = new TestController(new HttpRequest());
        $controller->run('unknownAction');
    }
}