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

use Phact\Main\Phact;
use Phact\Tests\Templates\AppTest;
use Phact\Translate\Translate;

class ModulesMessagesTest extends AppTest
{
    public function testDomains()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('тест', $translate->t('Test.main', 'test'));
        $this->assertEquals('Пользовательский тест', $translate->t('Test.custom', 'Custom test'));
        $this->assertEquals('Тест модуля', $translate->t('Test.messages', 'Module test'));
    }

    public function testModule()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('Тест модуля', $translate->t('Test', 'Module test'));
    }

    public function testPlural()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('1 элемент', $translate->t('Test', '%count% item|%count% items', 1));
        $this->assertEquals('2 элемента', $translate->t('Test', '%count% item|%count% items', 2));
        $this->assertEquals('5 элементов', $translate->t('Test', '%count% item|%count% items', 5));
    }
}