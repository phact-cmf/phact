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
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Fields\IntField;
use Phact\Orm\Model;

class NotePropertyIntValue extends Model
{
    public static function getFields()
    {
        return [
            'value' => [
                'class' => IntField::class,
                'null' => true
            ],
            'note_property' => [
                'class' => ForeignField::class,
                'modelClass' => NoteProperty::class
            ],
            'note' => [
                'class' => ForeignField::class,
                'modelClass' => Note::class
            ],
        ];
    }
}