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

use Modules\Test\Models\Note;
use Phact\Orm\Manager;
use Phact\Orm\QuerySet;

class QuerySetTest extends DatabaseTest
{
    public function testInstances()
    {
        $this->assertInstanceOf(Manager::class, Note::objects());
        $this->assertInstanceOf(QuerySet::class, Note::objects()->getQuerySet());
    }

    public function testCondition()
    {
        $this->assertInstanceOf(Manager::class, Note::objects());
        $this->assertInstanceOf(QuerySet::class, Note::objects()->getQuerySet());
    }
}