<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Model;

class MovieReview extends Model
{
    public static function getFields()
    {
        return [
            'movie' => [
                'class' => ForeignField::class,
                'modelClass' => Movie::class
            ],
            'text' => [
                'class' => CharField::class
            ]
        ];
    }
}
