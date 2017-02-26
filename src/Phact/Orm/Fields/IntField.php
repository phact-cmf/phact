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

    public $rawAccess = true;

    public function rawAccessValue($value)
    {
        return is_null($value) ? null : (int) $value;
    }

    public function attributePrepareValue($value)
    {
        return $this->rawAccessValue($value);
    }

    public function getValue($aliasConfig = null)
    {
        return $this->rawAccessValue($this->_attribute);
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