<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Fields\ManyToManyField;
use Phact\Orm\Model;

class Movie extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'genres' => [
                'class' => ManyToManyField::class,
                'modelClass' => Genre::class,
                'onUpdateTo' => ForeignField::CASCADE,
                'onDeleteTo' => ForeignField::NO_ACTION,
                'onUpdateFrom' => ForeignField::CASCADE,
                'onDeleteFrom' => ForeignField::RESTRICT,
            ],
            'film_company' => [
                'class' => ForeignField::class,
                'modelClass' => FilmCompany::class,
                'null' => true,
                'onDelete' => ForeignField::SET_NULL,
            ],
            'producer_country' => [
                'class' => ForeignField::class,
                'modelClass' => Country::class,
                'null' => true
            ],
            'reviews' => [
                'class' => HasManyField::class,
                'modelClass' => MovieReview::class
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
