<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/04/16 10:14
 */

namespace Phact\Tests\Cases\Orm\Sqlite;

use Phact\Tests\Cases\Orm\Abs\AbstractTableTest;

class SqliteTableTest extends AbstractTableTest
{
    protected $defaultConnection = 'sqlite';

    protected array $expectedConstraint = [
        'movie' => [
            'onUpdate' => 'CASCADE',
            'onDelete' => null
        ],

        'genre' => [
            'onUpdate' => 'CASCADE',
            'onDelete' => 'NO ACTION'
        ],

        'film_company' => [
            'onUpdate' => 'CASCADE',
            'onDelete' => 'SET NULL'
        ],
    ];
}