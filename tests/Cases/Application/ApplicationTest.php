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

use Phact\Application\Application;
use Phact\Helpers\Configurator;
use Phact\Helpers\Paths;
use Phact\Main\Phact;

class ApplicationTest extends TestCase
{
    public function getAppPath()
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'sandbox', 'app']);
    }

    public function testSimpleInit()
    {
        $config = [
            'name' => 'New phact application'
        ];
        Phact::init($config);
        $app = Phact::app();
        $this->assertEquals('New phact application', $app->name);
    }

    public function testComponentsInit()
    {
        $config = [
            'components' => [
                'std' => \stdClass::class
            ]
        ];
        Phact::init($config);
        $app = Phact::app();
        $this->assertInstanceOf(\stdClass::class, $app->std);
    }
}