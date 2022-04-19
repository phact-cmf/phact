<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\ManyToManyField;
use Phact\Orm\Model;

class Genre extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'movies' => [
                'class' => ManyToManyField::class,
                'modelClass' => Movie::class
            ]
        ];
    }
}
