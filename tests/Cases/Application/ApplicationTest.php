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
            'name' => 'New phact application',
            'paths' => [
                'base' => $this->getAppPath()
            ]
        ];
        Phact::init($config);
        $app = Phact::app();
        $this->assertEquals('New phact application', $app->name);
    }

    public function testComponentsInit()
    {
        $config = [
            'paths' => [
                'base' => $this->getAppPath()
            ],
            'servicesConfig' => $this->getAppPath() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'services_std.yml'
        ];
        Phact::init($config);
        $app = Phact::app();
        $this->assertInstanceOf(\stdClass::class, $app->std);
    }

    public function testPathsInit()
    {
        $config = [
            'paths' => [
                'base' => $this->getAppPath()
            ]
        ];
        Phact::init($config);
        $this->assertEquals(Paths::get('base'), $this->getAppPath());
        $this->assertEquals(Paths::get('runtime'), implode(DIRECTORY_SEPARATOR, [$this->getAppPath(), 'runtime']));
        $this->assertEquals(Paths::get('Modules'), implode(DIRECTORY_SEPARATOR, [$this->getAppPath(), 'Modules']));
    }
}