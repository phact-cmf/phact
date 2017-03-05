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
 * @date 12/04/16 18:51
 */

namespace Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\ManyToManyField;
use Phact\Orm\Model;

class Group extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'persons' => [
                'class' => ManyToManyField::class,
                'modelClass' => Person::class,
                'through' => Membership::class,
                'back' => 'groups'
            ]
        ];
    }
}