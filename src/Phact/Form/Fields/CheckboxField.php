<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 17/10/16 12:56
 */

namespace Phact\Form\Fields;


use Phact\Validators\NoZeroRequiredValidator;

class CheckboxField extends Field
{
    public $fieldTemplate = 'forms/field/checkbox/field.tpl';
    public $inputTemplate = 'forms/field/checkbox/input.tpl';

    public function setDefaultValidators()
    {
        if ($this->required) {
            $this->_validators[] = new NoZeroRequiredValidator($this->requiredMessage);
        }
    }

    public function render()
    {
        return $this->renderTemplate($this->fieldTemplate, [
            'input' => $this->renderInput(),
            'label' => $this->renderLabel(),
            'errors' => $this->renderErrors(),
            'hint' => $this->renderHint()
        ]);
    }
}