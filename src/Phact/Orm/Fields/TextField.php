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


use Phact\Form\Fields\TextAreaField;

class TextField extends CharField
{
    public function getSqlType()
    {
        return "TEXT";
    }

    public function getFormField()
    {
        return $this->setUpFormField([
            'class' => TextAreaField::class
        ]);
    }
}