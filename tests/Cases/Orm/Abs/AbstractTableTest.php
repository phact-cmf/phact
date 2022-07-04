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
use Phact\Tests\sandbox\app\Modules\Test\Models\FilmCompany;
use Phact\Tests\sandbox\app\Modules\Test\Models\Genre;
use Phact\Tests\sandbox\app\Modules\Test\Models\Movie;
use Phact\Tests\Templates\DatabaseTest;

abstract class AbstractTableTest extends DatabaseTest
{
    protected array $expectedConstraint = [];

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

    public function testConstrains()
    {
        $tableManager = new TableManager();
        $tableManager->processFk = true;
        $genreModel = new Genre();
        $movieModel = new Movie();
        $filmCompanyModel = new FilmCompany();
        $tableManager->create([
            $movieModel,
            $genreModel,
            $filmCompanyModel
        ]);

        $movieFieldManager = $movieModel->getFieldsManager();

        // M2M
        $m2mField = $movieFieldManager->getField('genres');
        $tableName = $m2mField->getThroughTableName();

        $schemaManager = $tableManager->getSchemaManager($movieModel);

        $list = $schemaManager->listTableForeignKeys($tableName);
        $this->assertCount(2, $list);

        foreach ($list as $constraint) {
            $actual = [
                'onUpdate' => $constraint->getOption('onUpdate'),
                'onDelete' => $constraint->getOption('onDelete')
            ];
            $expected = $this->expectedConstraint[$constraint->getForeignTableName()] ?? [];
            $this->assertEquals($expected, $actual, 'Incorrect constraint for ' . $constraint->getForeignTableName());
        }

        // FK
        $fkField = $movieFieldManager->getField('film_company');
        $tableName = $movieModel->getTableName();
        $constraint = current($schemaManager->listTableForeignKeys($tableName));
        $this->assertEquals(
            $this->expectedConstraint['film_company'],
            [
                'onUpdate' => $constraint->getOption('onUpdate'),
                'onDelete' => $constraint->getOption('onDelete'),
            ]
        );

        $tableManager->drop([
            new Genre(),
            new Movie(),
            new FilmCompany(),
        ]);
    }
}