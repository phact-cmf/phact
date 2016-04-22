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

use Modules\Test\Models\Area;
use Modules\Test\Models\Author;
use Modules\Test\Models\Group;
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
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['name' => 'Test', 'id__gte' => 10, 'theses__id__lte' => 5]);
    }

    public function testBuildRelations()
    {
        $qs = Area::objects()->getQuerySet();
        $qs->filter(['parent__id' => 1]);
        $qs->build();
        $this->assertEquals(['parent'], array_keys($qs->getRelations()));

        $qs = Author::objects()->getQuerySet();
        $qs->filter(['books__id__in' => [1,2,3]]);
        $qs->build();
        $this->assertEquals(['books'], array_keys($qs->getRelations()));

        $qs = Group::objects()->getQuerySet();
        $qs->filter(['persons__id__in' => [1,2,3], 'membership__id__gte' => 1]);
        $qs->build();
        $this->assertEquals(['membership', 'persons'], array_keys($qs->getRelations()));
    }
}