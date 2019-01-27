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

namespace Phact\Tests\Cases\Template;

use Phact\Main\Phact;
use Phact\Tests\Templates\AppTest;


class LibraryTest extends AppTest
{
    public function testUrl()
    {
        $tpl = Phact::app()->template;

        $this->assertEquals("some_value__TESTED\nTEST_PROPERTY\nTEST_ACCESSOR_FUNCTION_WITH_ARGUMENT_ARGUMENT\nTEST_FUNCTION_WITH_ARGUMENT_ARGUMENT", $tpl->render('defaults/library.tpl'));
    }
}