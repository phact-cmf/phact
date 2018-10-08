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

class TextField extends CharField
{
    public $length = null;

    public function getType()
    {
        return "text";
    }

    public function getFormField()
    {
        return $this->setUpFormField([
            'class' => TextAreaField::class
        ]);
    }
}