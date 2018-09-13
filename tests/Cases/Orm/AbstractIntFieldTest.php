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
use Modules\Test\Models\Person;
use Modules\Test\Models\Storage;
use Modules\Test\Models\Work;

abstract class AbstractIntFieldTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Storage()
        ];
    }

    public function testDefaultZero()
    {
        $storage = new Storage();
        $storage->name = "Default storage";
        $storage->free_spaces = 10;
        $this->assertTrue((bool) $storage->save());

        $storage->free_spaces = 0;
        $this->assertTrue((bool) $storage->save());

        $storage = Storage::objects()->get();
        $this->assertEquals(0, $storage->free_spaces);
    }
}