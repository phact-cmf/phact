<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 10/04/16 08:21
 */

namespace Phact\Tests;


use Phact\Template\TemplateManager;

class TemplatesPathsTest extends AppTest
{
    public function testSame()
    {
        $tpl = new TemplateManager();
        $tpl->init();
        $this->assertEquals($tpl->render('same.html'), 'Same template application');
        $this->assertEquals($tpl->render('module.html'), 'Module template');
    }
}