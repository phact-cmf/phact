<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Model;

class FilmCompany extends Model
{
    public static function getFields()
    {
        return [
            'movies' => [
                'class' => HasManyField::class,
                'modelClass' => Movie::class
            ]
        ];
    }
}
