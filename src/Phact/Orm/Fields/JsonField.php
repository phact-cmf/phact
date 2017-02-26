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
    public function rawAccessValue($value)
    {
        if (is_null($value)) {
            return null;
        }
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    public function getValue($aliasConfig = null)
    {
        return $this->rawAccessValue($this->_attribute);
    }

    public function attributePrepareValue($value)
    {
        return $this->rawAccessValue($value);
    }

    public function dbPrepareValue($value)
    {
        if (!is_string($value)) {
            $value = json_encode($value);
        }
        return (string) $value;
    }
}