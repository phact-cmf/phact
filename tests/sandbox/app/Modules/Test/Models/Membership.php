<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/04/16 18:51
 */

namespace Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\ManyToManyField;
use Phact\Orm\Model;

class Membership extends Model
{
    public static function getFields()
    {
        return [
            'role' => [
                'class' => CharField::class
            ],
            'person' => [
                'class' => ForeignField::class,
                'modelClass' => Person::class
            ],
            'group' => [
                'class' => ForeignField::class,
                'modelClass' => Group::class
            ]
        ];
    }
}