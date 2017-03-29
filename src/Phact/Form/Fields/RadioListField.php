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
 * @date 02/08/16 08:22
 */

namespace Phact\Form\Fields;

use Phact\Orm\Model;

class RadioListField extends Field
{
    /**
     * @var string
     */
    public $inputTemplate = 'forms/field/radio_list/input.tpl';

    public function setValue($value)
    {
        if ($value instanceof Model) {
            $value = $value->id;
        }
        $this->_value = $value;
        return $this;
    }
}