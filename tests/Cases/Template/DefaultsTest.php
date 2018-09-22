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


class DefaultsTest extends AppTest
{
    public function testUrl()
    {
        $tpl = Phact::app()->template;

        $this->assertEquals("/test_route\n/test_route/param", $tpl->render('defaults/url.tpl'));
        $this->assertEquals("/test_route\n/test_route/param", $tpl->render('defaults/url_accessor.tpl'));
    }

    public function testTranslate()
    {
        $tpl = Phact::app()->template;
        $translate = Phact::app()->translate;
        $translate->setLocale('ru');

        $this->assertEquals("Тест модуля\nтест\n1 элемент\n2 элемента", $tpl->render('defaults/t.tpl'));
//        $this->assertEquals("Тест модуля\nтест\n1 элемент\n2 элемента", $tpl->render('defaults/t_accessor.tpl'));
    }
}