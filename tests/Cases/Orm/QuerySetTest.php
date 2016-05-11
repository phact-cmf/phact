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
use Phact\Orm\Aggregations\Avg;
use Phact\Orm\Aggregations\Count;
use Phact\Orm\Expression;
use Phact\Orm\Manager;
use Phact\Orm\Q;
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
        $qs->filter(['name' => 'Test', 'id__gte' => 10, 'theses__id__lte' => 5, Q::orQ(['id' => 20], ['name' => 'Layla'])]);
        $sql = $qs->all(true);
        $this->assertEquals("SELECT DISTINCT `test_note`.* FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`name` = 'Test' AND `test_note`.`id` >= 10 AND `test_note_thesis`.`id` <= 5 AND ((`test_note`.`id` = 20) OR (`test_note`.`name` = 'Layla'))", $sql);
    }

    public function testExpressions()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['id__gt' => new Expression("{id} + {theses__id}"), new Expression("{id} <= {theses__id}")]);
        $sql = $qs->all(true);
        $this->assertEquals("SELECT DISTINCT `test_note`.* FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` > `test_note`.`id` + `test_note_thesis`.`id` AND `test_note`.`id` <= `test_note_thesis`.`id`", $sql);
    }

    public function testOrder()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->order(['-id', new Expression('{id} = 0')]);
        $sql = $qs->all(true);
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` ORDER BY `test_note`.`id` DESC, `test_note`.`id` = 0 ASC", $sql);
    }

    public function testBuildRelations()
    {
        $qs = Area::objects()->getQuerySet();
        $qs = $qs->filter(['parent__id' => 1]);
        $qs->build();
        $this->assertEquals(['parent'], array_keys($qs->getRelations()));

        $qs = Author::objects()->getQuerySet();
        $qs = $qs->filter(['books__id__in' => [1,2,3]]);
        $qs->build();
        $this->assertEquals(['books'], array_keys($qs->getRelations()));

        $qs = Group::objects()->getQuerySet();
        $qs = $qs->filter(['persons__id__in' => [1,2,3], 'membership__id__gte' => 1]);
        $qs->build();
        $this->assertEquals(['membership', 'persons'], array_keys($qs->getRelations()));
    }

    public function testLimitOffset()
    {
        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->limit(1)->all(true);
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 3 LIMIT 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->limit(1)->offset(2)->all(true);
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 3 LIMIT 1 OFFSET 2", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->offset(3)->all(true);
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 3 OFFSET 3", $sql);
    }

    public function testAggregations()
    {
        $sql = Note::objects()->getQuerySet()->aggregate(new Count(), true);
        $this->assertEquals("SELECT COUNT(*) as aggregation FROM `test_note`", $sql);

        $sql = Note::objects()->getQuerySet()->aggregate(new Avg('theses__id'), true);
        $this->assertEquals("SELECT AVG(`test_note_thesis`.`id`) as aggregation FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id`", $sql);
    }

    public function testGet()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->get(true);
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 1", $sql);
    }

    public function testPk()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->get(true);
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['theses__pk__in' => [1,2]])->all(true);
        $this->assertEquals("SELECT DISTINCT `test_note`.* FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note_thesis`.`id` IN (1, 2)", $sql);
    }

    public function testUpdate()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->update(['name' => 'Test'], true);
        $this->assertEquals("UPDATE `test_note` SET `test_note`.`name`='Test' WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->update(['name' => 'Test'], true);
        $this->assertEquals("UPDATE `test_note` SET `test_note`.`name`='Test' WHERE `test_note`.`id` IN (SELECT `temp_table_wrapper`.`id` FROM (SELECT `test_note`.`id` FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1 AND `test_note_thesis`.`id` IN (1, 2)) as `temp_table_wrapper`)", $sql);
    }

    public function testDelete()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->delete(true);
        $this->assertEquals("DELETE FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->delete(true);
        $this->assertEquals("UPDATE `test_note` SET `test_note`.`name`='Test' WHERE `test_note`.`id` IN (SELECT `temp_table_wrapper`.`id` FROM (SELECT `test_note`.`id` FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1 AND `test_note_thesis`.`id` IN (1, 2)) as `temp_table_wrapper`)", $sql);
    }
}