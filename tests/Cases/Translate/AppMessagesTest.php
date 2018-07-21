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

class AppMessagesTest extends AppTest
{
    public function testCustomMessages()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('Приложение', $translate->t('App', 'Application'));
    }

    public function testOverlappedMessages()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('Пользовательское правило', $translate->t('Test.messages', 'Custom rule'));
        $this->assertEquals('Пользовательское правило', $translate->t('Test', 'Custom rule'));
        $this->assertEquals('Тест модуля', $translate->t('Test', 'Module test'));
    }
}