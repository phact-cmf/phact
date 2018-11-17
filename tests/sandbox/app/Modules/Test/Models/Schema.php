<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 16/11/16 17:03
 */

namespace Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Model;

class Schema extends Model
{
    public static function getFields()
    {
        return [
            'key' => [
                'class' => CharField::class
            ],
            'group' => [
                'class' => CharField::class
            ],
            'order' => [
                'class' => CharField::class
            ]
        ];
    }
}