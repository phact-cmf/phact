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
 * @date 15/04/16 13:56
 */

namespace Phact\Orm\Fields;

class JsonField extends TextField
{
    public function getValue($aliasConfig = null)
    {
        if (is_null($this->_attribute)) {
            return null;
        }
        if (is_string($this->_attribute)) {
            return json_decode($this->_attribute, true);
        }
        return $this->_attribute;
    }

    public function dbPrepareValue($value)
    {
        if (!is_string($value)) {
            $value = json_encode($value);
        }
        return (string) $value;
    }
}