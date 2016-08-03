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
use Phact\Form\Form;
use Phact\Form\ModelForm;

class DefaultModelTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Book(),
            new Author()
        ];
    }

    /**
     * @return ModelForm
     */
    public function testCreate()
    {
        $book = new Book();
        $book->name = 'Alert';
        $form = new ModelForm(['instance' => $book, 'model' => $book]);
        return $form;
    }
}