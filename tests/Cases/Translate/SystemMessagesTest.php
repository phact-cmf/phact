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

namespace Phact\Tests;

use Phact\Main\Phact;
use Phact\Translate\Translate;
use Phact\Validators\EmailValidator;
use Phact\Validators\RequiredValidator;

class SystemMessagesTest extends AppTest
{
    public function testValidatorsMessages()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');
        $this->assertEquals('Некорректный e-mail', $translate->t('Incorrect e-mail', 'Phact.validators'));
        $translate->setLocale('en');
        $this->assertEquals('Incorrect e-mail', $translate->t('Incorrect e-mail', 'Phact.validators'));
    }

    public function testValidators()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;

        $translate->setLocale('ru');

        $validator = new RequiredValidator();
        $message = $validator->validate('');
        $this->assertEquals('Обязательно для заполнения', $message);

        $translate->setLocale('en');

        $validator = new EmailValidator();
        $message = $validator->validate('wrong e-mail');
        $this->assertEquals('Incorrect e-mail', $message);
    }
}