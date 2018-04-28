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


use Modules\Test\Forms\RequiredForm;
use Phact\Form\Form;

class RequiredTest extends AppTest
{
    public function testCreate()
    {
        $form = new RequiredForm();
        return $form;
    }

    /**
     * @depends testCreate
     * @param $form Form
     * @return Form
     */
    public function testNotFill($form)
    {
        $this->assertFalse($form->valid);
        $this->assertEquals([
            'one_field' => [
                'This field is required'
            ],
            'two_field' => [
                'This field is required'
            ]
        ], $form->getErrors());
    }

    /**
     * @depends testCreate
     * @param $form Form
     */
    public function testSetOneField($form)
    {
        $this->assertTrue($form->fill([
            'RequiredForm' => [
                'one_field' => 'value'
            ]
        ]));
        $this->assertFalse($form->valid);
        $this->assertEquals([
            'two_field' => [
                'This field is required'
            ]
        ], $form->getErrors());
    }

    /**
     * @depends testCreate
     * @param $form Form
     */
    public function testValid($form)
    {
        $this->assertTrue($form->fill([
            'RequiredForm' => [
                'one_field' => 'value',
                'two_field' => 'value'
            ]
        ]));
        $this->assertTrue($form->valid);
        $this->assertEquals([], $form->getErrors());
    }
}