<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 15/04/16 13:56
 */

namespace Phact\Orm\Fields;

use Phact\Form\Fields\TextAreaField;

class JsonField extends TextField
{
    public $editable = false;

    public $rawGet = true;

    public $rawSet = true;

    public function getValue($aliasConfig = null)
    {
        return $this->_attribute;
    }

    public function attributePrepareValue($value)
    {
        if (!isset($value)) {
            return null;
        }
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    public function dbPrepareValue($value)
    {
        if (!is_string($value)) {
            $value = json_encode($value);
        }
        return (string) $value;
    }

    public function getFormField()
    {
        return $this->setUpFormField([
            'class' => TextAreaField::class
        ]);
    }
}