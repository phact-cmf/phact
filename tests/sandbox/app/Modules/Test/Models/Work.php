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
use Phact\Orm\Fields\IntField;
use Phact\Orm\Model;

class Work extends Model
{
    const STATUS_STARTED = 1;
    const STATUS_DONE = 2;

    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'status' => [
                'class' => IntField::class,
                'choices' => [
                    self::STATUS_STARTED => 'Started',
                    self::STATUS_DONE => 'Done'
                ]
            ]
        ];
    }
}