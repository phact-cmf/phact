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
use Modules\Test\Models\BookCategory;
use Modules\Test\Models\Group;
use Modules\Test\Models\Membership;
use Modules\Test\Models\Note;
use Modules\Test\Models\NoteThesis;
use Modules\Test\Models\Schema;
use Phact\Orm\Fields\HasManyField;
use Phact\Tests\Templates\DatabaseTest;

abstract class AbstractQuotesTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Schema()
        ];
    }

    public function testCreate()
    {
        $schema = new Schema();
        $schema->setAttributes([
            'key' => 'key',
            'order' => 'order',
            'group' => 'group'
        ]);
        $this->assertTrue(!!$schema->save());
    }

    public function testUpdate()
    {
        $schema = new Schema();
        $schema->setAttributes([
            'key' => 'key',
            'order' => 'order',
            'group' => 'group'
        ]);
        $schema->save();

        $schema->setAttributes([
            'key' => 'index'
        ]);
        $schema->save();
        $this->assertTrue(!!$schema->save());
    }

    public function testFilter()
    {
        $schema = new Schema();
        $schema->setAttributes([
            'key' => 'key',
            'order' => 'order',
            'group' => 'group'
        ]);
        $schema->save();

        $result = Schema::objects()->filter([
            'key' => 'key'
        ])->order([
            'key'
        ])->all();

        $this->assertCount(1, $result);
    }

    public function testValues()
    {
        $expected = [
            'key' => 'key',
            'order' => 'order',
            'group' => 'group'
        ];
        $schema = new Schema();
        $schema->setAttributes($expected);
        $schema->save();

        $result = Schema::objects()->values(['key', 'group', 'order']);

        $this->assertEquals([$expected], $result);
    }
}