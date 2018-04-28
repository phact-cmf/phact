<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 05/03/17 12:25
 */

namespace Phact\Orm;


class Raw
{
    protected $_value;

    protected $_bindings = [];

    public function __construct($value, $bindings = array())
    {
        $this->_value = (string)$value;
        $this->_bindings = (array)$bindings;
    }

    public function getBindings()
    {
        return $this->_bindings;
    }

    public function getValue()
    {
        return $this->_bindings;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->_value;
    }
}