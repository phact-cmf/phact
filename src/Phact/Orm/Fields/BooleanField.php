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


class BooleanField extends IntField
{
    public $length = 1;
    public $unsigned = true;

    public function setAttribute($value)
    {
        parent::setAttribute($value);
    }

    public function getValue($aliasConfig = null)
    {
        return is_null($this->_attribute) ? null : (bool) $this->_attribute;
    }

    public function dbPrepareValue($value)
    {
        return $value ? 1 : 0;
    }

    public function mainSqlType()
    {
        return "TINYINT({$this->length})";
    }
}