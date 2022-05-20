<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Model;

class Movie extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'producer_country' => [
                'class' => ForeignField::class,
                'modelClass' => Country::class,
                'null' => true
            ]
        ];
    }

    public function fetchField($name)
    {
        if ($name === 'country_id') {
            $name = 'producer_country_id';
        }
        return parent::fetchField($name);
    }
}
