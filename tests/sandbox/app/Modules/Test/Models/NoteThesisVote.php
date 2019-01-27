<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 27/01/19 09:25
 */

namespace Modules\Test\Models;

use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\IntField;
use Phact\Orm\Model;

class NoteThesisVote extends Model
{
    public static function getFields()
    {
        return [
            'rating' => [
                'class' => IntField::class
            ],
            'note_thesis' => [
                'class' => ForeignField::class,
                'modelClass' => NoteThesis::class
            ]
        ];
    }
}