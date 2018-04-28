<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 07:30
 */

namespace Modules\Test\Forms;

use Phact\Form\Fields\CharField;
use Phact\Form\Form;
use Phact\Validators\RequiredValidator;

class RequiredForm extends Form
{
    public function getFields()
    {
        return [
            'one_field' => [
                'class' => CharField::class,
                'label' => 'Test one field label',
                'validators' => [
                    new RequiredValidator('This field is required')
                ]
            ],
            'two_field' => [
                'class' => CharField::class,
                'label' => 'Test two field label',
                'required' => true,
                'requiredMessage' => 'This field is required'
            ],
        ];
    }
}