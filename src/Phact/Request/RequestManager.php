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
 * @date 02/08/16 12:34
 */

namespace Phact\Request;

use Phact\Application\Application;
use Phact\Helpers\Configurator;

class RequestManager
{
    protected $_request;

    public $cliRequest;

    public $httpRequest;

    public function getRequest()
    {
        if (!$this->_request) {
            if (Application::getIsCliMode()) {
                $this->_request = Configurator::create($this->cliRequest);
            } else {
                $this->_request = Configurator::create($this->httpRequest);
            }
        }
        return $this->_request;
    }

    public function setRequest(Request $request)
    {
        $this->_request = $request;
    }

    public function __call($name, $arguments)
    {
        call_user_func_array([$this->getRequest(), $name], $arguments);
    }

    public function __get($name)
    {
        return $this->getRequest()->{$name};
    }

    public function __set($name, $value)
    {
        $this->getRequest()->{$name} = $value;
    }
}