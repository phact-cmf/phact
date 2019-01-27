<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/04/16 08:21
 */

namespace Phact\Tests\Cases\Translate;

use Modules\Test\Forms\TranslatedForm;
use Phact\Main\Phact;
use Phact\Tests\Templates\AppTest;
use Phact\Translate\Translate;

class TranslatorTest extends AppTest
{
    public function testModuleLocated()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $form = new TranslatedForm();
        $this->assertEquals('Тестовое поле', $form->getField('test_field')->getLabel());
    }
}