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
use Modules\Test\Models\NoteThesis;
use Phact\Orm\Aggregations\Avg;
use Phact\Orm\Aggregations\Count;
use Phact\Orm\Expression;
use Phact\Orm\Manager;
use Phact\Orm\Q;
use Phact\Orm\QuerySet;

class QuerySetTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Note(),
            new NoteThesis(),
            new Author(),
            new Area(),
            new Group()
        ];
    }

    public function testInstances()
    {
        $this->assertInstanceOf(Manager::class, Note::objects());
        $this->assertInstanceOf(QuerySet::class, Note::objects()->getQuerySet());
    }

    public function testCondition()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['name' => 'Test', 'id__gte' => 10, 'theses__id__lte' => 5, Q::orQ(['id' => 20], ['name' => 'Layla'])]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT DISTINCT `test_note`.* FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`name` = 'Test' AND `test_note`.`id` >= 10 AND `test_note_thesis`.`id` <= 5 AND ((`test_note`.`id` = 20) OR (`test_note`.`name` = 'Layla'))", $sql);
    }

    public function testExclude()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->exclude(['name' => 'Test']);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE NOT ((`test_note`.`name` = 'Test'))", $sql);
    }

    public function testExcludeFilter()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['name' => 'Actual']);
        $qs->exclude(['name' => 'Test']);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE (((`test_note`.`name` = 'Actual')) AND (NOT ((`test_note`.`name` = 'Test'))))", $sql);
    }

    public function testExpressions()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['id__gt' => new Expression("{id} + {theses__id}"), new Expression("{id} <= {theses__id}")]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT DISTINCT `test_note`.* FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` > `test_note`.`id` + `test_note_thesis`.`id` AND `test_note`.`id` <= `test_note_thesis`.`id`", $sql);
    }

    public function testOrder()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->order(['-id', new Expression('{id} = 0')]);
        $sql = $qs->allSql();
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
        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->limit(1)->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 3 LIMIT 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->limit(1)->offset(2)->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 3 LIMIT 1 OFFSET 2", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->offset(3)->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 3 OFFSET 3", $sql);
    }

    public function testAggregations()
    {
        $sql = Note::objects()->getQuerySet()->aggregateSql(new Count());
        $this->assertEquals("SELECT COUNT(*) as aggregation FROM `test_note`", $sql);

        $sql = Note::objects()->getQuerySet()->aggregateSql(new Avg('theses__id'));
        $this->assertEquals("SELECT AVG(`test_note_thesis`.`id`) as aggregation FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id`", $sql);
    }

    public function testGet()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->getSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 1", $sql);
    }

    public function testPk()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->getSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['theses__pk__in' => [1,2]])->allSql();
        $this->assertEquals("SELECT DISTINCT `test_note`.* FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note_thesis`.`id` IN (1, 2)", $sql);
    }

    public function testUpdate()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->updateSql(['name' => 'Test']);
        $this->assertEquals("UPDATE `test_note` SET `test_note`.`name`='Test' WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->updateSql(['name' => 'Test']);
        $this->assertEquals("UPDATE `test_note` SET `test_note`.`name`='Test' WHERE `test_note`.`id` IN (SELECT `temp_table_wrapper`.`id` FROM (SELECT `test_note`.`id` FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1 AND `test_note_thesis`.`id` IN (1, 2)) as `temp_table_wrapper`)", $sql);
    }

    public function testDelete()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->deleteSql();
        $this->assertEquals("DELETE FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->deleteSql();
        $this->assertEquals("DELETE FROM `test_note` WHERE `test_note`.`id` IN (SELECT `temp_table_wrapper`.`id` FROM (SELECT `test_note`.`id` FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1 AND `test_note_thesis`.`id` IN (1, 2)) as `temp_table_wrapper`)", $sql);
    }

    public function testValues()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name']);
        $this->assertEquals("SELECT `test_note`.`id` as `id`, `test_note`.`name` as `name` FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name', 'theses__name']);
        $this->assertEquals("SELECT DISTINCT `test_note`.`id` as `id`, `test_note`.`name` as `name`, `test_note_thesis`.`name` as `theses__name` FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name'], true);
        $this->assertEquals("SELECT `test_note`.`id` as `id`, `test_note`.`name` as `name` FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name', 'theses__name'], false, false);
        $this->assertEquals("SELECT `test_note`.`id` as `id`, `test_note`.`name` as `name`, `test_note_thesis`.`name` as `theses__name` FROM `test_note` INNER JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1", $sql);
    }

    public function testChoices()
    {
        $note1 = new Note();
        $note1->name = 'First note';
        $note1->save();

        $note2 = new Note();
        $note2->name = 'Second note';
        $note2->save();

        $data = Note::objects()->getQuerySet()->choices('id', 'name');
        $this->assertEquals([
            1 => 'First note',
            2 => 'Second note'
        ], $data);
    }
}