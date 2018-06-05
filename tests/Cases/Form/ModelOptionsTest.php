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

use Modules\Test\Forms\CompanyDefaultForm;
use Modules\Test\Forms\CompanyFoundedForm;
use Modules\Test\Models\Company;

class ModelOptionsTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Company()
        ];
    }

    public function testOnlyForm()
    {
        $form = new CompanyFoundedForm();
        $fields = $form->getInitFields();
        $this->assertEquals([
            'founded'
        ], array_keys($fields));
    }

    public function testExcludedNew()
    {
        $form = new CompanyDefaultForm();
        $fields = $form->getInitFields();
        $this->assertEquals([
            'name'
        ], array_keys($fields));
    }
}