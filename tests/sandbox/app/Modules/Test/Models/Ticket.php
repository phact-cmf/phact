<?php

namespace Phact\Tests\sandbox\app\Modules\Test\Models;

use Phact\Orm\Fields\DateTimeField;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Model;

class Ticket extends Model
{
    public static function getFields()
    {
        return [
            'movie' => [
                'class' => ForeignField::class,
                'modelClass' => Movie::class,
            ],
            'date' => [
                'class' => DateTimeField::class,
            ],
            'ticket_order' => [
                'class' => ForeignField::class,
                'modelClass' => TicketOrder::class
            ]
        ];
    }
}
