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

namespace Phact\Tests\Cases\Orm\Abs;

use Modules\Test\Models\Author;
use Modules\Test\Models\Book;
use Modules\Test\Models\Group;
use Modules\Test\Models\Membership;
use Modules\Test\Models\Note;

use Modules\Test\Models\NoteThesis;
use Modules\Test\Models\Person;
use Phact\Orm\TableManager;
use Phact\Tests\Templates\DatabaseTest;

abstract class AbstractTableTest extends DatabaseTest
{
    public function testCreate()
    {
        $tableManager = new TableManager();
        $this->assertTrue($tableManager->create([
            new Note(),
            new NoteThesis(),
            new Author(),
            new Book(),
            new Person(),
            new Group(),
            new Membership()
        ]));
        $this->assertTrue($tableManager->drop([
            new Note(),
            new NoteThesis(),
            new Author(),
            new Book(),
            new Person(),
            new Group(),
            new Membership()
        ]));
    }
}