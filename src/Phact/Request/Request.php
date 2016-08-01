<?php

namespace Phact\Request;

use Phact\Cli\Cli;
use Phact\Helpers\Collection;
use Phact\Helpers\SmartProperties;

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
 * @date 13/06/16 11:08
 */
class Request
{
    use SmartProperties;

    /**
     * @var Http
     */
    public $http;

    /**
     * @var Cli
     */
    public $cli;

    public function init()
    {
        $this->http = new Http();
        $this->cli = new Cli();
    }
}