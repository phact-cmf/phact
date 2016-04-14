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

use Phact\Application\Application;
use Phact\Helpers\Configurator;
use Phact\Helpers\Paths;

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
            ],
            'components' => [
                'std' => [
                    'class' => '\stdClass'
                ]
            ]
        ];
        $app = Configurator::create(Application::class, $config);
        $this->assertEquals('New phact application', $app->name);
        $this->assertInstanceOf(\stdClass::class, $app->std);
    }

    public function testComponentsInit()
    {
        $config = [
            'paths' => [
                'base' => $this->getAppPath()
            ],
            'components' => [
                'std' => [
                    'class' => '\stdClass'
                ]
            ]
        ];
        $app = Configurator::create(Application::class, $config);
        $this->assertInstanceOf(\stdClass::class, $app->std);
    }

    public function testPathsInit()
    {
        $config = [
            'paths' => [
                'base' => $this->getAppPath()
            ]
        ];
        $app = Configurator::create(Application::class, $config);
        $this->assertEquals(Paths::get('base'), $this->getAppPath());
        $this->assertEquals(Paths::get('runtime'), implode(DIRECTORY_SEPARATOR, [$this->getAppPath(), 'runtime']));
        $this->assertEquals(Paths::get('Modules'), implode(DIRECTORY_SEPARATOR, [$this->getAppPath(), 'Modules']));
    }
}