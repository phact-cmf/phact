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
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;


class IntField extends NumericField
{
    public $length = 11;

    public $rawGet = true;

    public $rawSet = true;

    public function attributePrepareValue($value)
    {
        return isset($value) ? (int) $value : null;
    }

    public function getValue($aliasConfig = null)
    {
        return $this->_attribute;
    }

    public function dbPrepareValue($value)
    {
        return (int) $value;
    }

    public function mainSqlType()
    {
        return "INT({$this->length})";
    }
}