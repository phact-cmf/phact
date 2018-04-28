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
use Modules\Test\Models\Book;
use Modules\Test\Models\Group;
use Modules\Test\Models\Membership;
use Modules\Test\Models\Note;
use Modules\Test\Models\NoteThesis;
use Phact\Orm\Fields\HasManyField;

class BasicRelationsTest extends DatabaseTest
{
    public function testHasManyNames()
    {
        $note = new Note();
        /* @var $field \Phact\Orm\Fields\HasManyField */
        $field = $note->getField('theses');
        $this->assertEquals('note_id', $field->getTo());
        $this->assertEquals('id', $field->getFrom());
    }

    public function testForeignNames()
    {
        $note = new NoteThesis();
        /* @var $field \Phact\Orm\Fields\HasManyField */
        $field = $note->getField('note');
        $this->assertEquals('id', $field->getTo());
        $this->assertEquals('note_id', $field->getFrom());
    }

    public function testManyToManyNames()
    {
        $author = new Author();
        /* @var $field \Phact\Orm\Fields\ManyToManyField */
        $field = $author->getField('books');
        $this->assertEquals('id', $field->getTo());
        $this->assertEquals('id', $field->getFrom());
        $this->assertEquals('author_id', $field->getThroughFrom());
        $this->assertEquals('book_id', $field->getThroughTo());
        $this->assertEquals('test_author_test_book', $field->getThroughTableName());

        $book = new Book();
        /* @var $field \Phact\Orm\Fields\ManyToManyField */
        $field = $book->getField('authors');
        $this->assertEquals('id', $field->getTo());
        $this->assertEquals('id', $field->getFrom());
        $this->assertEquals('book_id', $field->getThroughFrom());
        $this->assertEquals('author_id', $field->getThroughTo());
        $this->assertEquals('test_author_test_book', $field->getThroughTableName());

        $group = new Group();
        /* @var $field \Phact\Orm\Fields\ManyToManyField */
        $field = $group->getField('persons');
        $this->assertEquals('id', $field->getTo());
        $this->assertEquals('id', $field->getFrom());
        $this->assertEquals('group_id', $field->getThroughFrom());
        $this->assertEquals('person_id', $field->getThroughTo());
        $this->assertEquals('test_membership', $field->getThroughTableName());
        $this->assertEquals(Membership::class, $field->getThrough());

        /* @var $membershipField \Phact\Orm\Fields\HasManyField */
        $membershipField = $group->getField('membership');
        $this->assertInstanceOf(HasManyField::class, $membershipField);
        $this->assertEquals("persons", $membershipField->getThroughFor());
    }
}