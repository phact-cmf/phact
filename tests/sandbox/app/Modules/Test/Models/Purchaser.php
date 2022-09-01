<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Model;

class Purchaser extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'ticket_orders' => [
                'class' => HasManyField::class,
                'modelClass' => TicketOrder::class,
            ]
        ];
    }
}
