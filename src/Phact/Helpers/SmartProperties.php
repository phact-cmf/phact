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
 * @date 09/04/16 10:35
 */

namespace Phact\Helpers;


use Phact\Exceptions\UnknownPropertyException;

trait SmartProperties
{
    public function __get($name)
    {
        return $this->__smartGet($name);
    }

    public function __set($name, $value)
    {
        $this->__smartSet($name, $value);
    }

    public function __smartGet($name)
    {
        $method = 'get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            throw new UnknownPropertyException('Unknown property ' . $name);
        }
    }

    public function __smartSet($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        } else {
            throw new UnknownPropertyException('Unknown property ' . $name);
        }
    }
}