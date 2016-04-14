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
use Phact\Main\Phact;

class AppTest extends TestCase
{
    public function getAppPath()
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'sandbox', 'app']);
    }

    public function getModules()
    {
        $path = implode(DIRECTORY_SEPARATOR, [$this->getAppPath(), 'Modules']);
        $list = glob($path . DIRECTORY_SEPARATOR . '*');
        return $list;
    }

    protected function setUp()
    {
        $config = [
            'name' => 'New phact application',
            'paths' => [
                'base' => $this->getAppPath()
            ],
            'modules' => $this->getModules(),
            'components' => $this->getComponents()
        ];
        Phact::init($config);
    }
    
    protected function getComponents()
    {
        return [];
    }
}