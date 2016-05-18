<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 10/04/16 10:14
 */

namespace Phact\Tests;

use Modules\Test\Models\Author;
use Modules\Test\Models\Book;
use Modules\Test\Models\Group;
use Modules\Test\Models\Membership;
use Modules\Test\Models\Note;

use Modules\Test\Models\NoteThesis;
use Modules\Test\Models\Person;
use Phact\Orm\TableManager;

class TableTest extends DatabaseTest
{
    public function testCreate()
    {
        $tableManager = new TableManager();
        $tableManager->create([
            new Note(),
            new NoteThesis(),
            new Author(),
            new Book(),
            new Person(),
            new Group(),
            new Membership()
        ]);
        $tableManager->drop([
            new Note(),
            new NoteThesis(),
            new Author(),
            new Book(),
            new Person(),
            new Group(),
            new Membership()
        ]);
    }
}