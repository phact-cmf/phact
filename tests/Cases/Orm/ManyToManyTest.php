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

namespace Phact\Tests;

use Modules\Test\Models\Author;
use Modules\Test\Models\Blogger;
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
            new Membership(),
            new Blogger()
        ];
    }

    public function testDefaultNonBack()
    {
        $author = new Author();
        $author->name = 'JK Rowling';
        $author->save();
        $this->assertEquals("SELECT `test_book`.* FROM `test_book` LEFT JOIN `test_author_test_book` ON `test_book`.`id` = `test_author_test_book`.`book_id` WHERE `test_author_test_book`.`author_id` = 1", $author->books->getQuerySet()->allSql());
    }

    public function testDefaultBack()
    {
        $group = new Group();
        $group->name = 'Test group';
        $group->save();
        $this->assertEquals("SELECT DISTINCT `test_person`.* FROM `test_person` LEFT JOIN `test_membership` ON `test_person`.`id` = `test_membership`.`person_id` WHERE `test_membership`.`group_id` = 1", $group->persons->getQuerySet()->allSql());
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

    public function testQueriesNonBack()
    {
        $book1 = new Book();
        $book1->name = "The Cuckoo's Calling";
        $book1->save();

        $book2 = new Book();
        $book2->name = "The Silkworm";
        $book2->save();

        $author = new Author();
        $author->name = 'JK Rowling';
        $author->books = [$book1, $book2->id];
        $author->save();

        $this->assertEquals("SELECT DISTINCT `test_author`.* FROM `test_author` LEFT JOIN `test_author_test_book` ON `test_author`.`id` = `test_author_test_book`.`author_id` LEFT JOIN `test_book` ON `test_author_test_book`.`book_id` = `test_book`.`id` WHERE `test_book`.`id` IN (1)", Author::objects()->filter(['books__id__in' => [$book1->id]])->allSql());
        $this->assertEquals(1, Author::objects()->filter(['books__id__in' => [$book1->id]])->count());

        $this->assertEquals("SELECT DISTINCT `test_book`.* FROM `test_book` LEFT JOIN `test_author_test_book` ON `test_book`.`id` = `test_author_test_book`.`book_id` LEFT JOIN `test_author` ON `test_author_test_book`.`author_id` = `test_author`.`id` WHERE `test_author`.`id` IN (1)", Book::objects()->filter(['authors__id__in' => [$author->id]])->allSql());
        $this->assertEquals(2, Book::objects()->filter(['authors__id__in' => [$author->id]])->count());

        $this->assertEquals("SELECT DISTINCT `test_book`.* FROM `test_book` LEFT JOIN `test_author_test_book` ON `test_book`.`id` = `test_author_test_book`.`book_id` LEFT JOIN `test_author` ON `test_author_test_book`.`author_id` = `test_author`.`id` WHERE `test_author`.`name` LIKE '%JK%'", Book::objects()->filter(['authors__name__contains' => 'JK'])->allSql());
        $this->assertEquals(2, Book::objects()->filter(['authors__name__contains' => 'JK'])->count());
    }

    public function testQueriesBack()
    {
        $person1 = new Person();
        $person1->name = "Cormoran Strike";
        $person1->save();

        $person2 = new Person();
        $person2->name = "Robin Ellacott";
        $person2->save();

        $group = new Group();
        $group->name = 'Detectives';
        $group->persons = [$person1, $person2->id];
        $group->save();

        $this->assertEquals(
            "SELECT DISTINCT `test_group`.* FROM `test_group` LEFT JOIN `test_membership` ON `test_group`.`id` = `test_membership`.`group_id` LEFT JOIN `test_person` ON `test_membership`.`person_id` = `test_person`.`id` WHERE `test_person`.`id` IN (1)",
            Group::objects()->filter(['persons__id__in' => [$person1->id]])->allSql()
        );
        $this->assertEquals(1, Group::objects()->filter(['persons__id__in' => [$person1->id]])->count());

        $this->assertEquals(
            "SELECT DISTINCT `test_group`.* FROM `test_group` LEFT JOIN `test_membership` ON `test_group`.`id` = `test_membership`.`group_id` LEFT JOIN `test_person` ON `test_membership`.`person_id` = `test_person`.`id` WHERE `test_person`.`id` IN (2) AND `test_person`.`name` LIKE '%Robin%'",
            Group::objects()->filter(['persons__id__in' => [$person2->id], 'persons__name__contains' => 'Robin'])->allSql()
        );
        $this->assertEquals(1, Group::objects()->filter(['persons__id__in' => [$person2->id], 'persons__name__contains' => 'Robin'])->count());

        $this->assertEquals(
            "SELECT DISTINCT `test_group`.* FROM `test_group` LEFT JOIN `test_membership` ON `test_group`.`id` = `test_membership`.`group_id` WHERE `test_membership`.`role` = 'Director'",
            Group::objects()->filter(['membership__role' => 'Director'])->allSql()
        );
        $this->assertEquals(0, Group::objects()->filter(['membership__role' => 'Director'])->count());

        $this->assertEquals(
            "SELECT DISTINCT `test_group`.* FROM `test_group` LEFT JOIN `test_membership` ON `test_group`.`id` = `test_membership`.`group_id` LEFT JOIN `test_person` ON `test_membership`.`person_id` = `test_person`.`id` WHERE `test_membership`.`role` = 'Director' AND `test_person`.`name` LIKE '%Albert%'",
            Group::objects()->filter(['membership__role' => 'Director', 'persons__name__contains' => 'Albert'])->allSql()
        );
        $this->assertEquals(0, Group::objects()->filter(['membership__role' => 'Director', 'persons__name__contains' => 'Albert'])->count());


        $this->assertEquals(
            "SELECT DISTINCT `test_person`.* FROM `test_person` LEFT JOIN `test_membership` ON `test_person`.`id` = `test_membership`.`person_id` LEFT JOIN `test_group` ON `test_membership`.`group_id` = `test_group`.`id` WHERE `test_group`.`id` IN (1)",
            Person::objects()->filter(['groups__id__in' => [$person1->id]])->allSql()
        );
        $this->assertEquals(2, Person::objects()->filter(['groups__id__in' => [$group->id]])->count());

        $this->assertEquals(
            "SELECT DISTINCT `test_person`.* FROM `test_person` LEFT JOIN `test_membership` ON `test_person`.`id` = `test_membership`.`person_id` LEFT JOIN `test_group` ON `test_membership`.`group_id` = `test_group`.`id` WHERE `test_group`.`name` LIKE '%tive%'",
            Person::objects()->filter(['groups__name__contains' => 'tive'])->allSql()
        );
        $this->assertEquals(2, Person::objects()->filter(['groups__name__contains' => 'tive'])->count());
    }

    public function testThroughModels()
    {
        $person1 = new Person();
        $person1->name = "Cormoran Strike";
        $person1->save();

        $person2 = new Person();
        $person2->name = "Robin Ellacott";
        $person2->save();

        $group = new Group();
        $group->name = 'Detectives';
        $group->save();

        $membership1 = new Membership();
        $membership1->person = $person1;
        $membership1->group = $group;
        $membership1->role = 'Detective';
        $membership1->save();

        $person2->groups->link($group, [
            'role' => 'Assistant'
        ]);

        $this->assertEquals(
            "SELECT DISTINCT `test_group`.* FROM `test_group` LEFT JOIN `test_membership` ON `test_group`.`id` = `test_membership`.`group_id` WHERE `test_membership`.`role` = 'Detective'",
            Group::objects()->filter(['membership__role' => 'Detective'])->allSql()
        );
        $this->assertEquals(1, Group::objects()->filter(['membership__role' => 'Detective'])->count());
        $this->assertEquals(1, Group::objects()->filter(['membership__role' => 'Assistant'])->count());
    }

    public function testSameModel()
    {
        $blogger1 = new Blogger();
        $blogger1->name = 'Roland Deschain';
        $blogger1->save();

        $blogger2 = new Blogger();
        $blogger2->name = 'Jake Chambers';
        $blogger2->save();

        $blogger3 = new Blogger();
        $blogger3->name = 'Eddie Dean';
        $blogger3->save();

        $blogger1->subscribes = [$blogger2, $blogger3];
        $blogger1->save();

        $this->assertEquals(1, $blogger2->subscribers->count());
        $this->assertEquals(1, $blogger3->subscribers->count());

        $this->assertEquals(
            "SELECT `test_blogger`.* FROM `test_blogger` LEFT JOIN `test_blogger_subscribes` ON `test_blogger`.`id` = `test_blogger_subscribes`.`to_id` WHERE (`test_blogger_subscribes`.`from_id` = 1) AND (`test_blogger`.`name` LIKE '%and%')",
            $blogger1->subscribes->filter(['name__contains' => 'and'])->allSql()
        );

        $this->assertEquals(
            "SELECT `test_blogger`.* FROM `test_blogger` LEFT JOIN `test_blogger_subscribes` ON `test_blogger`.`id` = `test_blogger_subscribes`.`from_id` WHERE (`test_blogger_subscribes`.`to_id` = 1) AND (`test_blogger`.`name` LIKE 'Rol%')",
            $blogger1->subscribers->filter(['name__startswith' => 'Rol'])->allSql()
        );
        $this->assertEquals(1, $blogger3->subscribers->filter(['name__startswith' => 'Rol'])->count());

        $this->assertEquals(
            "SELECT DISTINCT `test_blogger`.* FROM `test_blogger` LEFT JOIN `test_blogger_subscribes` ON `test_blogger`.`id` = `test_blogger_subscribes`.`from_id` LEFT JOIN `test_blogger_subscribes` AS `test_blogger_subscribes_1` ON `test_blogger`.`id` = `test_blogger_subscribes_1`.`from_id` LEFT JOIN `test_blogger` AS `test_blogger_2` ON `test_blogger_subscribes_1`.`to_id` = `test_blogger_2`.`id` WHERE (`test_blogger_subscribes`.`to_id` = 3) AND (`test_blogger_2`.`name` LIKE 'Eddie%')",
            $blogger3->subscribers->filter(['subscribes__name__startswith' => 'Eddie'])->allSql()
        );
        $this->assertEquals(1, $blogger3->subscribers->filter(['subscribes__name__startswith' => 'Eddie'])->count());

        $this->assertEquals(0, $blogger1->subscribers->count());
        $this->assertEquals(2, $blogger1->subscribes->count());
    }
}