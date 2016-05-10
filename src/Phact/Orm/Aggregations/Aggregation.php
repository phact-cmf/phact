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
 * @date 10/05/16 10:36
 */

namespace Phact\Orm\Aggregations;


abstract class Aggregation
{
    protected $_field = '*';
    protected $_raw = false;

    public function __construct($field = '*', $raw = false)
    {
        $this->_field = $field;
        $this->_raw = $raw;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function getRaw()
    {
        return $this->_field == '*' || $this->_raw;
    }

    abstract public function getSql($field);
}