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
use Phact\Translate\Translator;

class TranslatedForm extends Form
{
    use Translator;

    public function getFields()
    {
        return [
            'test_field' => [
                'class' => CharField::class,
                'label' => self::t('Test field')
            ]
        ];
    }
}