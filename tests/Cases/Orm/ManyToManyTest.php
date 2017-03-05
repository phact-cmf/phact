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
use Modules\Test\Models\Person;

class ManyToManyTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Author(),
            new Book(),
            new Group(),
            new Person(),
            new Membership()
        ];
    }

    public function testDefaultNonBack()
    {
        $author = new Author();
        $author->name = 'JK Rowling';
        $author->save();
        $this->assertEquals("SELECT `test_book`.* FROM `test_book` INNER JOIN `test_author_test_book` ON `test_book`.`id` = `test_author_test_book`.`book_id` WHERE `test_author_test_book`.`author_id` = 1", $author->books->getQuerySet()->allSql());
    }

    public function testDefaultBack()
    {
        $group = new Group();
        $group->name = 'Test group';
        $group->save();
        $this->assertEquals("SELECT DISTINCT `test_person`.* FROM `test_person` INNER JOIN `test_membership` ON `test_person`.`id` = `test_membership`.`person_id` WHERE `test_membership`.`group_id` = 1", $group->persons->getQuerySet()->allSql());
    }

    public function testSetNonBack()
    {
        $author = new Author();
        $author->name = 'JK Rowling';
        $author->save();

        $book1 = new Book();
        $book1->name = "The Cuckoo's Calling";
        $book1->save();

        $book2 = new Book();
        $book2->name = "The Silkworm";
        $book2->save();

        $this->assertEquals(0, $author->books->count());

        $author->books = [$book1, $book2->id];
        $author->save();

        $this->assertEquals(["The Cuckoo's Calling", "The Silkworm"], $author->books->values(['name'], true));

        $author->books->unlink($book1);

        $this->assertEquals(["The Silkworm"], $author->books->values(['name'], true));

        $author->books = [];
        $author->save();

        $this->assertEquals(0, $author->books->count());
    }

    public function testSetBack()
    {
        $group = new Group();
        $group->name = 'Detectives';
        $group->save();

        $person1 = new Person();
        $person1->name = "Cormoran Strike";
        $person1->save();

        $person2 = new Person();
        $person2->name = "Robin Ellacott";
        $person2->save();

        $this->assertEquals(0, $group->persons->count());

        $group->persons = [$person1, $person2->id];
        $group->save();

        $this->assertEquals(["Cormoran Strike", "Robin Ellacott"], $group->persons->values(['name'], true));

        $group->persons->unlink($person1);

        $this->assertEquals(["Robin Ellacott"], $group->persons->values(['name'], true));

        $group->persons = [];
        $group->save();

        $this->assertEquals(0, $group->persons->count());
    }
}