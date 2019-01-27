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

use Phact\Main\Phact;
use Phact\Tests\Templates\TestCase;

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