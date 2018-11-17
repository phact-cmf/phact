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
use Modules\Test\Models\BookCategory;
use Modules\Test\Models\Group;
use Modules\Test\Models\Membership;
use Modules\Test\Models\Note;
use Modules\Test\Models\NoteThesis;
use Modules\Test\Models\Schema;
use Phact\Orm\Fields\HasManyField;

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
    }

    public function testFilter()
    {
        Schema::objects()->filter([
            'key' => 'key'
        ])->order([
            'key'
        ])->all();
    }

    public function testValues()
    {
        Schema::objects()->values(['key', 'group', 'order']);
    }
}