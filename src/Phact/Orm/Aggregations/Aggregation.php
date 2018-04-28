<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/05/16 10:36
 */

namespace Phact\Orm\Aggregations;


use Exception;
use Phact\Orm\Expression;

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

    public static function expression($field = '*', $alias = null)
    {
        $sql = static::getSql($field);
        if ($alias) {
            $sql .= ' as ' . $alias;
        }
        return new Expression($sql);
    }

    /**
     * @param $field
     * @return string
     * @throws Exception
     */
    public static function getSql($field)
    {
        throw new Exception('Not implemented');
    }
}