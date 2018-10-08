<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23/09/2018
 * Time: 18:57
 */

namespace Phact\Template;

use Fenom;
use Exception;

class ExtendedFenom extends Fenom
{
    protected $_callables = [];

    protected $_props = [];

    public function addCallable($name, $callable)
    {
        $this->_callables[$name] = $callable;
    }

    public function addProp($name, $callable)
    {
        $this->_props[$name] = $callable;
    }

    public function addAccessorCallable($name, $callable)
    {
        $callableName = "callable__{$name}";
        $this->addCallable($callableName, $callable);
        $this->addAccessorSmart($name, '$tpl->getStorage()->{"' . $callableName .'"}', Fenom::ACCESSOR_CALL);
    }

    public function addAccessorProp($name, $callable)
    {
        $callableName = "prop__{$name}";
        $this->addProp($callableName, $callable);
        $this->addAccessorSmart($name, '{"' . $callableName .'"}', Fenom::ACCESSOR_PROPERTY);
    }

    public function __call($method, $arguments)
    {
        if (isset($this->_callables[$method])) {
            return call_user_func_array($this->_callables[$method], $arguments);
        }
        throw new Exception("No method {$method} found in Fenom");
    }

    public function __get($name)
    {
        if (isset($this->_props[$name])) {
            return call_user_func_array($this->_props[$name], []);
        }
        throw new Exception("No property {$name} found in Fenom");
    }
}