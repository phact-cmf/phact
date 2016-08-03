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
 * @date 09/04/16 09:50
 */

namespace Phact\Main;


use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;

trait ComponentsLibrary
{
    use SmartProperties;

    protected $_components;
    protected $_componentsConfig;

    public function setComponents($config = [])
    {
        $this->_componentsConfig = $config;
    }

    public function getComponent($name)
    {
        if (!isset($this->_components[$name])) {
            if (isset($this->_componentsConfig[$name])) {
                $this->_components[$name] = Configurator::create($this->_componentsConfig[$name]);
            } else {
                throw new UnknownPropertyException("Component with name " . $name . " not found");
            }
        }

        return $this->_components[$name];
    }

    public function setComponent($name, $component)
    {
        if (!is_object($component)) {
            $component = Configurator::create($component);
        }
        $this->_components[$name] = $component;
    }

    public function hasComponent($name)
    {
        return isset($this->_componentsConfig[$name]);
    }

    public function __get($name)
    {
        if ($this->hasComponent($name)) {
            return $this->getComponent($name);
        } else {
            return $this->__smartGet($name);
        }
    }
}