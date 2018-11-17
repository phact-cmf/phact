<?php
/**
 *
 *
 * All rights {$q}reserved{$q}.
 *
 * @author Okulov Anton
 * @email qantus@{$q}mail{$q}.{$q}ru{$q}
 * @version {$q}1{$q}.{$q}0{$q}
 * @date 10/04/16 10:14
 */

namespace Phact\Tests;

use Modules\Test\Models\Author;
use Modules\Test\Models\Blogger;
use Modules\Test\Models\Book;
use Modules\Test\Models\Group;
use Modules\Test\Models\Membership;
use Modules\Test\Models\Person;

abstract class AbstractManyToManyTest extends DatabaseTest
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
        $q = $this->getQuoteCharacter();
        $this->assertEquals("SELECT {$q}test_book{$q}.* FROM {$q}test_book{$q} LEFT JOIN {$q}test_author_test_book{$q} {$q}test_author_test_book_1{$q} ON {$q}test_book{$q}.{$q}id{$q} = {$q}test_author_test_book_1{$q}.{$q}book_id{$q} WHERE {$q}test_author_test_book_1{$q}.{$q}author_id{$q} = 1", $author->books->getQuerySet()->allSql());
    }

    public function testDefaultBack()
    {
        $group = new Group();
        $group->name = 'Test group';
        $group->save();
        $q = $this->getQuoteCharacter();
        $this->assertEquals("SELECT DISTINCT {$q}test_person{$q}.* FROM {$q}test_person{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_person{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}person_id{$q} WHERE {$q}test_membership_1{$q}.{$q}group_id{$q} = 1", $group->persons->getQuerySet()->allSql());
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

        $this->assertEquals(["Cormoran Strike", "Robin Ellacott"], $group->persons->order(['name'])->values(['name'], true));

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


        $q = $this->getQuoteCharacter();

        $this->assertEquals("SELECT DISTINCT {$q}test_author{$q}.* FROM {$q}test_author{$q} LEFT JOIN {$q}test_author_test_book{$q} {$q}test_author_test_book_1{$q} ON {$q}test_author{$q}.{$q}id{$q} = {$q}test_author_test_book_1{$q}.{$q}author_id{$q} LEFT JOIN {$q}test_book{$q} {$q}test_book_2{$q} ON {$q}test_author_test_book_1{$q}.{$q}book_id{$q} = {$q}test_book_2{$q}.{$q}id{$q} WHERE {$q}test_book_2{$q}.{$q}id{$q} IN (1)", Author::objects()->filter(['books__id__in' => [$book1->id]])->allSql());
        $this->assertEquals(1, Author::objects()->filter(['books__id__in' => [$book1->id]])->count());

        $this->assertEquals("SELECT DISTINCT {$q}test_book{$q}.* FROM {$q}test_book{$q} LEFT JOIN {$q}test_author_test_book{$q} {$q}test_author_test_book_1{$q} ON {$q}test_book{$q}.{$q}id{$q} = {$q}test_author_test_book_1{$q}.{$q}book_id{$q} LEFT JOIN {$q}test_author{$q} {$q}test_author_2{$q} ON {$q}test_author_test_book_1{$q}.{$q}author_id{$q} = {$q}test_author_2{$q}.{$q}id{$q} WHERE {$q}test_author_2{$q}.{$q}id{$q} IN (1)", Book::objects()->filter(['authors__id__in' => [$author->id]])->allSql());
        $this->assertEquals(2, Book::objects()->filter(['authors__id__in' => [$author->id]])->count());

        $this->assertEquals("SELECT DISTINCT {$q}test_book{$q}.* FROM {$q}test_book{$q} LEFT JOIN {$q}test_author_test_book{$q} {$q}test_author_test_book_1{$q} ON {$q}test_book{$q}.{$q}id{$q} = {$q}test_author_test_book_1{$q}.{$q}book_id{$q} LEFT JOIN {$q}test_author{$q} {$q}test_author_2{$q} ON {$q}test_author_test_book_1{$q}.{$q}author_id{$q} = {$q}test_author_2{$q}.{$q}id{$q} WHERE {$q}test_author_2{$q}.{$q}name{$q} LIKE '%JK%'", Book::objects()->filter(['authors__name__contains' => 'JK'])->allSql());
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

        $q = $this->getQuoteCharacter();

        $this->assertEquals(
            "SELECT DISTINCT {$q}test_group{$q}.* FROM {$q}test_group{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_group{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}group_id{$q} LEFT JOIN {$q}test_person{$q} {$q}test_person_2{$q} ON {$q}test_membership_1{$q}.{$q}person_id{$q} = {$q}test_person_2{$q}.{$q}id{$q} WHERE {$q}test_person_2{$q}.{$q}id{$q} IN (1)",
            Group::objects()->filter(['persons__id__in' => [$person1->id]])->allSql()
        );
        $this->assertEquals(1, Group::objects()->filter(['persons__id__in' => [$person1->id]])->count());

        $this->assertEquals(
            "SELECT DISTINCT {$q}test_group{$q}.* FROM {$q}test_group{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_group{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}group_id{$q} LEFT JOIN {$q}test_person{$q} {$q}test_person_2{$q} ON {$q}test_membership_1{$q}.{$q}person_id{$q} = {$q}test_person_2{$q}.{$q}id{$q} WHERE ({$q}test_person_2{$q}.{$q}id{$q} IN (2)) AND ({$q}test_person_2{$q}.{$q}name{$q} LIKE '%Robin%')",
            Group::objects()->filter(['persons__id__in' => [$person2->id], 'persons__name__contains' => 'Robin'])->allSql()
        );
        $this->assertEquals(1, Group::objects()->filter(['persons__id__in' => [$person2->id], 'persons__name__contains' => 'Robin'])->count());

        $this->assertEquals(
            "SELECT DISTINCT {$q}test_group{$q}.* FROM {$q}test_group{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_group{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}group_id{$q} WHERE {$q}test_membership_1{$q}.{$q}role{$q} = 'Director'",
            Group::objects()->filter(['membership__role' => 'Director'])->allSql()
        );
        $this->assertEquals(0, Group::objects()->filter(['membership__role' => 'Director'])->count());

        $this->assertEquals(
            "SELECT DISTINCT {$q}test_group{$q}.* FROM {$q}test_group{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_group{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}group_id{$q} LEFT JOIN {$q}test_person{$q} {$q}test_person_2{$q} ON {$q}test_membership_1{$q}.{$q}person_id{$q} = {$q}test_person_2{$q}.{$q}id{$q} WHERE ({$q}test_membership_1{$q}.{$q}role{$q} = 'Director') AND ({$q}test_person_2{$q}.{$q}name{$q} LIKE '%Albert%')",
            Group::objects()->filter(['membership__role' => 'Director', 'persons__name__contains' => 'Albert'])->allSql()
        );
        $this->assertEquals(0, Group::objects()->filter(['membership__role' => 'Director', 'persons__name__contains' => 'Albert'])->count());


        $this->assertEquals(
            "SELECT DISTINCT {$q}test_person{$q}.* FROM {$q}test_person{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_person{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}person_id{$q} LEFT JOIN {$q}test_group{$q} {$q}test_group_2{$q} ON {$q}test_membership_1{$q}.{$q}group_id{$q} = {$q}test_group_2{$q}.{$q}id{$q} WHERE {$q}test_group_2{$q}.{$q}id{$q} IN (1)",
            Person::objects()->filter(['groups__id__in' => [$person1->id]])->allSql()
        );
        $this->assertEquals(2, Person::objects()->filter(['groups__id__in' => [$group->id]])->count());

        $this->assertEquals(
            "SELECT DISTINCT {$q}test_person{$q}.* FROM {$q}test_person{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_person{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}person_id{$q} LEFT JOIN {$q}test_group{$q} {$q}test_group_2{$q} ON {$q}test_membership_1{$q}.{$q}group_id{$q} = {$q}test_group_2{$q}.{$q}id{$q} WHERE {$q}test_group_2{$q}.{$q}name{$q} LIKE '%tive%'",
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

        $q = $this->getQuoteCharacter();
        $this->assertEquals(
            "SELECT DISTINCT {$q}test_group{$q}.* FROM {$q}test_group{$q} LEFT JOIN {$q}test_membership{$q} {$q}test_membership_1{$q} ON {$q}test_group{$q}.{$q}id{$q} = {$q}test_membership_1{$q}.{$q}group_id{$q} WHERE {$q}test_membership_1{$q}.{$q}role{$q} = 'Detective'",
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

        $q = $this->getQuoteCharacter();
        
        $this->assertEquals(
            "SELECT {$q}test_blogger{$q}.* FROM {$q}test_blogger{$q} LEFT JOIN {$q}test_blogger_subscribes{$q} {$q}test_blogger_subscribes_1{$q} ON {$q}test_blogger{$q}.{$q}id{$q} = {$q}test_blogger_subscribes_1{$q}.{$q}to_id{$q} WHERE ({$q}test_blogger_subscribes_1{$q}.{$q}from_id{$q} = 1) AND ({$q}test_blogger{$q}.{$q}name{$q} LIKE '%and%')",
            $blogger1->subscribes->filter(['name__contains' => 'and'])->allSql()
        );

        $this->assertEquals(
            "SELECT {$q}test_blogger{$q}.* FROM {$q}test_blogger{$q} LEFT JOIN {$q}test_blogger_subscribes{$q} {$q}test_blogger_subscribes_1{$q} ON {$q}test_blogger{$q}.{$q}id{$q} = {$q}test_blogger_subscribes_1{$q}.{$q}from_id{$q} WHERE ({$q}test_blogger_subscribes_1{$q}.{$q}to_id{$q} = 1) AND ({$q}test_blogger{$q}.{$q}name{$q} LIKE 'Rol%')",
            $blogger1->subscribers->filter(['name__startswith' => 'Rol'])->allSql()
        );
        $this->assertEquals(1, $blogger3->subscribers->filter(['name__startswith' => 'Rol'])->count());

        $this->assertEquals(
            "SELECT DISTINCT {$q}test_blogger{$q}.* FROM {$q}test_blogger{$q} LEFT JOIN {$q}test_blogger_subscribes{$q} {$q}test_blogger_subscribes_1{$q} ON {$q}test_blogger{$q}.{$q}id{$q} = {$q}test_blogger_subscribes_1{$q}.{$q}from_id{$q} LEFT JOIN {$q}test_blogger_subscribes{$q} {$q}test_blogger_subscribes_2{$q} ON {$q}test_blogger{$q}.{$q}id{$q} = {$q}test_blogger_subscribes_2{$q}.{$q}from_id{$q} LEFT JOIN {$q}test_blogger{$q} {$q}test_blogger_3{$q} ON {$q}test_blogger_subscribes_2{$q}.{$q}to_id{$q} = {$q}test_blogger_3{$q}.{$q}id{$q} WHERE ({$q}test_blogger_subscribes_1{$q}.{$q}to_id{$q} = 3) AND ({$q}test_blogger_3{$q}.{$q}name{$q} LIKE 'Eddie%')",
            $blogger3->subscribers->filter(['subscribes__name__startswith' => 'Eddie'])->allSql()
        );
        $this->assertEquals(1, $blogger3->subscribers->filter(['subscribes__name__startswith' => 'Eddie'])->count());

        $this->assertEquals(0, $blogger1->subscribers->count());
        $this->assertEquals(2, $blogger1->subscribes->count());
    }
}