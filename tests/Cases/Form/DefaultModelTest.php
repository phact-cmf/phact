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


use Modules\Test\Forms\RequiredForm;
use Modules\Test\Models\Author;
use Modules\Test\Models\Book;
use Modules\Test\Models\Company;
use Phact\Form\Form;
use Phact\Form\ModelForm;

class DefaultModelTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Book(),
            new Author(),
            new Company()
        ];
    }

    /**
     * @return ModelForm
     */
    public function testCreateNew()
    {
        $book = new Book();
        $form = new ModelForm(['model' => $book]);
        return $form;
    }

    /**
     * @depends testCreateNew
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testNotFill($form)
    {
        $this->assertFalse($form->valid);
        $this->assertEquals(1, count($form->getErrors()));
        return $form;
    }

    /**
     * @depends testCreateNew
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testFill($form)
    {
        $this->assertTrue(
            $form->fill([
                $form->getName() => [
                    'name' => 'Test book'
                ]
            ])
        );
        $this->assertTrue($form->valid);
        $this->assertEmpty($form->getErrors());
        $this->assertEquals([
            'name' => 'Test book'
        ], $form->getAttributes());
        return $form;
    }

    /**
     * @depends testFill
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testSave($form)
    {
        $form->save();
        $instance = $form->getInstance();
        $pk = $instance->pk;
        $dbInstance = $instance::objects()->filter(['pk' => $pk])->limit(1)->get();
        $this->assertEquals($dbInstance->name, $form->getField('name')->getValue());
        return $form;
    }

    /**
     * @return ModelForm
     */
    public function testCreateInstance()
    {
        $company = new Company();
        $company->name = 'P and P Company';
        $form = new ModelForm(['model' => $company, 'instance' => $company]);
        return $form;
    }

    /**
     * @depends testCreateInstance
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testAttributesInstance($form)
    {
        $this->assertEquals([
            'name' => 'P and P Company',
            'founded' => null
        ], $form->getAttributes());
        return $form;
    }

    /**
     * @depends testCreateInstance
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testNotFillInstance($form)
    {
        $this->assertFalse($form->valid);
        $this->assertEquals(1, count($form->getErrors()));
        return $form;
    }

    /**
     * @depends testCreateInstance
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testValidFillInstance($form)
    {
        $this->assertTrue(
            $form->fill([
                $form->getName() => [
                    'founded' => '2015'
                ]
            ])
        );
        $this->assertTrue($form->valid);
        $this->assertEmpty($form->getErrors());
        $this->assertEquals([
            'name' => 'P and P Company',
            'founded' => '2015'
        ], $form->getAttributes());
        return $form;
    }

    /**
     * @depends testCreateInstance
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testInvalidFillInstance($form)
    {
        $this->assertTrue(
            $form->fill([
                $form->getName() => [
                    'name' => null,
                    'founded' => '2015'
                ]
            ])
        );
        $this->assertFalse($form->valid);
        $this->assertNotEmpty($form->getErrors());
        $this->assertEquals([
            'name' => null,
            'founded' => '2015'
        ], $form->getAttributes());
        return $form;
    }

    /**
     * @depends testValidFillInstance
     * @param $form ModelForm
     * @return ModelForm
     */
    public function testSaveInstance($form)
    {
        $form->save();
        $instance = $form->getInstance();
        $pk = $instance->pk;
        $dbInstance = $instance::objects()->filter(['pk' => $pk])->limit(1)->get();
        $this->assertEquals($dbInstance->name, $form->getField('name')->getValue());
        $this->assertEquals($dbInstance->founded, $form->getField('founded')->getValue());
        return $form;
    }
}