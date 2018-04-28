<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 16/01/17 16:09
 */

namespace Modules\Test\Models;


use Phact\Orm\Fields\CharField;
use Phact\Orm\TreeModel;

class BookCategory extends TreeModel
{
    public static function getFields()
    {
        return array_merge(parent::getFields(), [
            'name' => [
                'class' => CharField::class,
                'label' => "Name"
            ]
        ]);
    }
}