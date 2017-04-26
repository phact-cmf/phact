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

use Phact\Orm\Manager;
use Phact\Orm\Model;

class CheckboxListField extends Field
{
    /**
     * @var string
     */
    public $inputTemplate = 'forms/field/checkbox_list/input.tpl';

    public $disabled = [];

    public $emptyText = null;

    public $multiple = true;

    public function setValue($value)
    {
        if ($value instanceof Model) {
            $value = $value->id;
        }
        if ($value instanceof Manager) {
            $value = $value->values(['id'], true);
        }
        $this->_value = $value;
        return $this;
    }
}