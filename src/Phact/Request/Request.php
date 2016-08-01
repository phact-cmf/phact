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
 * @date 13/06/16 11:08
 */

namespace Phact\Request;

use Phact\Cli\Cli;
use Phact\Helpers\Collection;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;

/**
 * Class Request
 *
 * @property \Phact\Request\Session $session
 *
 * @package Phact\Request
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

    /**
     * Alias to session
     * @return Session
     */
    public function getSession()
    {
        return Phact::app()->session;
    }
}