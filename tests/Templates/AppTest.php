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
use Phact\Main\Phact;

class AppTest extends TestCase
{
    protected function getComponents()
    {
        return [];
    }

    protected function setUp()
    {
        $config = include implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'sandbox', 'app', 'config', 'settings.php']);
        $config['components'] = array_merge($config['components'], $this->getComponents());
        Phact::init($config);
    }
}