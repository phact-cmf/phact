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

class ApplicationTest extends TestCase
{
    public function testInit()
    {
        $config = [
            'name' => 'New phact application',
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
}