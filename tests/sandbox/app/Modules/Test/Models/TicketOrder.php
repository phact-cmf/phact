<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\DateTimeField;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Model;

class TicketOrder extends Model
{
    public static function getFields()
    {
        return [
            'created_at' => [
                'class' => DateTimeField::class,
            ],
            'tickets' => [
                'class' => HasManyField::class,
                'modelClass' => Ticket::class,
                'null' => true
            ],
            'purchaser' => [
                'class' => ForeignField::class,
                'modelClass' => Purchaser::class
            ],
            'call_manager' => [
                'class' => ForeignField::class,
                'modelClass' => CallManager::class
            ]
        ];
    }
}
