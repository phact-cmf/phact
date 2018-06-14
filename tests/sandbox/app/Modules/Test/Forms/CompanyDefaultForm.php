<?php

/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 05/06/18 16:39
 */
namespace Modules\Test\Forms;

use Modules\Test\Models\Company;
use Phact\Form\ModelForm;

class CompanyDefaultForm extends ModelForm
{
    public $exclude = [
        'founded'
    ];

    public function getModel()
    {
        return new Company();
    }
}