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


class IntField extends Field
{
    public $length = 11;

    public function getBlankValue()
    {
        return '';
    }

    public function getValue($aliasConfig = null)
    {
        return is_null($this->_attribute) ? null : (int) $this->_attribute;
    }

    public function dbPrepareValue($value)
    {
        return (int) $value;
    }

    public function getSqlType()
    {
        $sql = ["int({$this->length})"];
        if ($this->unsigned) {
            $sql[] = "UNSIGNED";
        }
        if ($this->zerofill) {
            $sql[] = "ZEROFILL";
        }
        return implode(' ', $sql);
    }
}