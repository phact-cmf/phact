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
 * @date 17/10/16 12:56
 */

namespace Phact\Form\Fields;


class CheckboxField extends Field
{
    public $fieldTemplate = 'forms/field/checkbox/field.tpl';
    public $inputTemplate = 'forms/field/checkbox/input.tpl';

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