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

class CharField extends Field
{
    public $length = 255;

    public function getValue($aliasConfig = null)
    {
        return is_null($this->_attribute) ? null : (string) $this->_attribute;
    }

    public function dbPrepareValue($value)
    {
        return (string) $value;
    }

    public function getSqlType()
    {
        return "VARCHAR({$this->length})";
    }
}