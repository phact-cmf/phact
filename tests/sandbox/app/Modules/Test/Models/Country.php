<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Model;

class Country extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'movies' => [
                'class' => HasManyField::class,
                'modelClass' => Movie::class
            ]
        ];
    }
}
