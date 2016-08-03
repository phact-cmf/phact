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
 * @date 02/08/16 07:30
 */

namespace Modules\Test\Forms;

use Phact\Form\Fields\CharField;
use Phact\Form\Form;

class SimpleForm extends Form
{
    public function getFields()
    {
        return [
            'one_field' => [
                'class' => CharField::class,
                'label' => 'Test one field label'
            ]
        ];
    }
}