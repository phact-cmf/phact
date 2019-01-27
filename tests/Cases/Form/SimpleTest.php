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

namespace Phact\Tests\Cases\Form;


use Modules\Test\Forms\SimpleForm;
use Phact\Form\Form;
use Phact\Tests\Templates\AppTest;

class SimpleTest extends AppTest
{
    public function testCreate()
    {
        $form = new SimpleForm();
        $this->assertInstanceOf(SimpleForm::class, $form);
        return $form;
    }

    /**
     * @depends testCreate
     * @param $form Form
     * @return Form
     */
    public function testFill($form)
    {
        $data = [
            'SimpleForm' => [
                'one_field' => 'value'
            ]
        ];
        $this->assertTrue($form->fill($data));
        $this->assertEquals(['one_field' => 'value'], $form->getAttributes());
        return $form;
    }

    /**
     * @depends testFill
     * @param $form Form
     */
    public function testValid($form)
    {
        $this->assertTrue($form->valid);
    }
}