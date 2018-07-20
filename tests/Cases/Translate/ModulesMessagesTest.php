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

class ModulesMessagesTest extends AppTest
{
    public function testDomains()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('тест', $translate->t('test', 'Test.main'));
        $this->assertEquals('Пользовательский тест', $translate->t('Custom test', 'Test.custom'));
        $this->assertEquals('Тест модуля', $translate->t('Module test', 'Test.messages'));
    }

    public function testModule()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('Тест модуля', $translate->t('Module test', 'Test'));
    }

    public function testPlural()
    {
        /** @var Translate $translate */
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals('1 элемент', $translate->t('%count% item|%count% items', 'Test', 1));
        $this->assertEquals('2 элемента', $translate->t('%count% item|%count% items', 'Test', 2));
        $this->assertEquals('5 элементов', $translate->t('%count% item|%count% items', 'Test', 5));
    }
}