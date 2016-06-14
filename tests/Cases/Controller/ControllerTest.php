<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 09/04/16 11:26
 */

namespace Phact\Tests;

use InvalidArgumentException;
use Modules\Test\Controllers\TestController;
use Phact\Controller\Controller;
use Phact\Helpers\Paths;
use Phact\Main\Phact;
use Phact\Request\Request;
use Phact\Router\Router;

class ControllerTest extends AppTest
{
    public function testSimple()
    {
        $this->expectOutputString('test');
        $controller = new TestController(new Request());
        $controller->run('test');
    }

    public function testMatchSimple()
    {
        $this->expectOutputString('test');

        $router = Phact::app()->router;
        $match = $router->match('/test_route', 'GET');

        $controllerClass = $match['target'][0];
        $action = $match['target'][1];
        $params = $match['params'];

        /** @var Controller $controller */
        $controller = new $controllerClass(new Request());
        $controller->run($action, $params);
    }

    public function testParams()
    {
        $this->expectOutputString('Name: params_test');
        $controller = new TestController(new Request());
        $controller->run('testParam', ['name' => 'params_test']);
    }

    public function testMatchParams()
    {
        $this->expectOutputString('Name: params_test');

        $router = Phact::app()->router;
        $match = $router->match('/test_route/params_test', 'GET');

        $controllerClass = $match['target'][0];
        $action = $match['target'][1];
        $params = $match['params'];

        /** @var Controller $controller */
        $controller = new $controllerClass(new Request());
        $controller->run($action, $params);
    }

    /**
     * @expectedException \Phact\Exceptions\InvalidConfigException
     */
    public function testInvalidParams()
    {
        $controller = new TestController(new Request());
        $controller->run('testParam', ['id' => 'params_test']);
    }


    /**
     * @expectedException \Phact\Exceptions\InvalidConfigException
     */
    public function testUnknownAction()
    {
        $controller = new TestController(new Request());
        $controller->run('unknownAction');
    }
}