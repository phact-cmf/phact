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

use Phact\Main\Phact;
use Phact\Request\HttpRequest;
use Phact\Router\Router;

class WebApplicationTest extends TestCase
{
    public function makeApp()
    {
        $config = [
            'name' => 'New phact application',
            'components' => [
                'router' => [
                    'class' => Router::class,
                    'calls' => [
                        'collect' => [
                            'configuration' => [
                                [
                                    'route' => '/arguments_route_correct/{:value}/{:name}',
                                    'target' => [ArgumentsTestController::class, 'arguments'],
                                    'name' => 'arguments_correct'
                                ],
                                [
                                    'route' => '/arguments_route_incorrect/{:incorrect}/{:name}',
                                    'target' => [ArgumentsTestController::class, 'arguments'],
                                    'name' => 'arguments_incorrect'
                                ],
                                [
                                    'route' => '/di/{:name}',
                                    'target' => [ArgumentsTestController::class, 'di'],
                                    'name' => 'di'
                                ],
                                [
                                    'route' => '/di_reversed/{:name}',
                                    'target' => [ArgumentsTestController::class, 'diReversed'],
                                    'name' => 'di_reversed'
                                ],
                                [
                                    'route' => '/di_controller',
                                    'target' => [DiTestController::class, 'test'],
                                    'name' => 'di_controller'
                                ],
                            ]
                        ]
                    ]
                ],
                'request' => [
                    'class' => HttpTestRequest::class,
                ]
            ]
        ];
        Phact::init($config);
        return Phact::app();
    }

    public function testArgumented()
    {
        $app = $this->makeApp();

        ob_start();
        $app->getComponent('request')->url = "/arguments_route_correct/value/name";
        $app->handleWebRequest();
        $result = ob_get_clean();
        $this->assertEquals("name - value", $result);

        ob_start();
        $app->getComponent('request')->url = "/arguments_route_incorrect/value/name";
        $app->handleWebRequest();
        $result = ob_get_clean();
        $this->assertEquals("name - ", $result);
    }

    public function testControllerDi()
    {
        $app = $this->makeApp();

        ob_start();
        $app->getComponent('request')->url = "/di_controller";
        $app->handleWebRequest();
        $result = ob_get_clean();
        $this->assertEquals(Router::class, $result);
    }

    public function testActionDi()
    {
        $app = $this->makeApp();

        ob_start();
        $app->getComponent('request')->url = "/di/name";
        $app->handleWebRequest();
        $result = ob_get_clean();
        $this->assertEquals("name - " . Router::class, $result);

        ob_start();
        $app->getComponent('request')->url = "/di_reversed/name";
        $app->handleWebRequest();
        $result = ob_get_clean();
        $this->assertEquals("name - " . Router::class, $result);
    }
}