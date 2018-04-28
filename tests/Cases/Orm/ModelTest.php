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
use Modules\Test\Models\Work;

class ModelTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Work()
        ];
    }

    public function testChoices()
    {
        $work = new Work();
        $work->status = Work::STATUS_DONE;
        $this->assertEquals(true, $work->isStatusDone);
        $this->assertEquals(false, $work->isStatusStarted);
    }
}