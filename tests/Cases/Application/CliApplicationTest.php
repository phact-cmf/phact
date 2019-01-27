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

namespace Phact\Tests\Cases\Application;

use Modules\Test\Commands\TestCommand;
use Phact\Main\Phact;
use Phact\Request\HttpRequest;
use Phact\Router\Router;
use Phact\Tests\Mock\CliTestRequest;
use Phact\Tests\Templates\TestCase;

class CliApplicationTest extends TestCase
{
    public function makeApp()
    {
        $config = [
            'name' => 'New phact application',
            'modules' => [
                'Test'
            ],
            'components' => [
                'router' => [
                    'class' => Router::class,
                ],
                'cliRequest' => [
                    'class' => CliTestRequest::class
                ]
            ]
        ];
        Phact::init($config);
        return Phact::app();
    }

    public function testCommandDi()
    {
        $app = $this->makeApp();
        ob_start();
        $app->getComponent('cliRequest')->match = [TestCommand::class, 'handle', []];
        $app->handleCliRequest();
        $result = ob_get_clean();
        $this->assertEquals(Router::class, $result);
    }
}