<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 13/04/16 08:11
 */

namespace Phact\Orm\Fields;

class EmailField extends CharField
{
    public function getFormField()
    {
        return $this->setUpFormField([
            'class' => \Phact\Form\Fields\EmailField::class
        ]);
    }
}