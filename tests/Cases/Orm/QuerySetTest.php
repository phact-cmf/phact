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
use Modules\Test\Models\NoteProperty;
use Modules\Test\Models\NotePropertyCharValue;
use Modules\Test\Models\NotePropertyIntValue;
use Modules\Test\Models\NoteThesis;
use Phact\Orm\Aggregations\Avg;
use Phact\Orm\Aggregations\Count;
use Phact\Orm\Expression;
use Phact\Orm\Having\Having;
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
            new Group(),
            new NoteProperty(),
            new NotePropertyCharValue(),
            new NotePropertyIntValue(),
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
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`name` = 'Test' AND `test_note`.`id` >= 10 AND `test_note_thesis`.`id` <= 5 AND ((`test_note`.`id` = 20) OR (`test_note`.`name` = 'Layla')) GROUP BY `test_note`.`id`", $sql);
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
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` > `test_note`.`id` + `test_note_thesis`.`id` AND `test_note`.`id` <= `test_note_thesis`.`id` GROUP BY `test_note`.`id`", $sql);

        $qs = Note::objects()->getQuerySet();
        $qs->filter([new Expression("{id} + {theses__id} > ?", [2000])]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` + `test_note_thesis`.`id` > 2000 GROUP BY `test_note`.`id`", $sql);

        $qs = Note::objects()->getQuerySet();
        $sql = $qs->valuesSql(['id', new Expression("({id} + {theses__id}) as s")]);
        $this->assertEquals("SELECT DISTINCT `test_note`.`id` as `id`, (`test_note`.`id` + `test_note_thesis`.`id`) as s FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id`", $sql);
    }

    public function testOrder()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->order(['-id', new Expression('{id} = ? ASC', [0])]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` ORDER BY `test_note`.`id` DESC, `test_note`.`id` = 0 ASC", $sql);
    }

    public function testSubQuery()
    {
        $qs = Note::objects()->getQuerySet();
        $qs->filter([
            'id__in' => NoteThesis::objects()->filter(['id__gt' => 20])->select(['note_id'])
        ]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` IN (SELECT `note_id` FROM `test_note_thesis` WHERE `test_note_thesis`.`id` > 20)", $sql);

        $qs = Note::objects()->getQuerySet();
        $qs->filter([
            'id__gt' => NoteThesis::objects()->filter(['id__gt' => new Expression('`test_note`.`id`')])->select([new Expression('COUNT(*)')])
        ]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` WHERE `test_note`.`id` > (SELECT COUNT(*) FROM `test_note_thesis` WHERE `test_note_thesis`.`id` > `test_note`.`id`)", $sql);
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
        $this->assertEquals("SELECT AVG(`test_note_thesis`.`id`) as aggregation FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id`", $sql);
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
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note_thesis`.`id` IN (1, 2) GROUP BY `test_note`.`id`", $sql);
    }

    public function testUpdate()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->updateSql(['name' => 'Test']);
        $this->assertEquals("UPDATE `test_note` SET `test_note`.`name`='Test' WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->updateSql(['name' => 'Test']);
        $this->assertEquals("UPDATE `test_note` SET `test_note`.`name`='Test' WHERE `test_note`.`id` IN (SELECT `temp_table_wrapper`.`id` FROM (SELECT `test_note`.`id` FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1 AND `test_note_thesis`.`id` IN (1, 2)) as `temp_table_wrapper`)", $sql);
    }

    public function testDelete()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->deleteSql();
        $this->assertEquals("DELETE FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->deleteSql();
        $this->assertEquals("DELETE FROM `test_note` WHERE `test_note`.`id` IN (SELECT `temp_table_wrapper`.`id` FROM (SELECT `test_note`.`id` FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1 AND `test_note_thesis`.`id` IN (1, 2)) as `temp_table_wrapper`)", $sql);
    }

    public function testValues()
    {
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name']);
        $this->assertEquals("SELECT `test_note`.`id` as `id`, `test_note`.`name` as `name` FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name', 'theses__name']);
        $this->assertEquals("SELECT DISTINCT `test_note`.`id` as `id`, `test_note`.`name` as `name`, `test_note_thesis`.`name` as `theses__name` FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name'], true);
        $this->assertEquals("SELECT `test_note`.`id` as `id`, `test_note`.`name` as `name` FROM `test_note` WHERE `test_note`.`id` = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name', 'theses__name'], false, false);
        $this->assertEquals("SELECT `test_note`.`id` as `id`, `test_note`.`name` as `name`, `test_note_thesis`.`name` as `theses__name` FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` WHERE `test_note`.`id` = 1", $sql);
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

    public function testHaving()
    {
        $note1 = new Note();
        $note1->name = 'First note';
        $note1->save();

        $note2 = new Note();
        $note2->name = 'Second note';
        $note2->save();
        
        $thesis1 = new NoteThesis();
        $thesis1->note = $note1;
        $thesis1->name = 'First thesis';
        $thesis1->save();

        $thesis2 = new NoteThesis();
        $thesis2->note = $note1;
        $thesis2->name = 'Second thesis';
        $thesis2->save();

        //$count = Note::objects()->getQuerySet()->select(['*', Count::expression('{theses__id}', 'count_theses')])->having(new Expression('count_theses > 1'))->allSql();
        $sql = Note::objects()->getQuerySet()->having(new Having(new Count('theses__id'), '>= 1'))->allSql();
        $this->assertEquals("SELECT `test_note`.*, COUNT(`test_note_thesis`.`id`) as hav FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` GROUP BY `test_note`.`id` HAVING hav >= 1", $sql);

        $all = Note::objects()->getQuerySet()->having(new Having(new Count('theses__id'), '>= 1'))->all();
        $this->assertEquals(1, count($all));

        $this->assertEquals("First note", $all[0]->name);
    }

    public function testOrderWithMany()
    {
        $note1 = new Note();
        $note1->name = 'First note';
        $note1->save();

        $thesis1 = new NoteThesis();
        $thesis1->note = $note1;
        $thesis1->name = 'First thesis';
        $thesis1->save();

        $thesis2 = new NoteThesis();
        $thesis2->note = $note1;
        $thesis2->name = 'Second thesis';
        $thesis2->save();

        $qs = Note::objects()->order(['-theses__name']);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.*, `test_note_thesis`.`name` AS `order__theses__name` FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` GROUP BY `test_note`.`id` ORDER BY `order__theses__name` DESC", $sql);

        $sql = $qs->valuesSql(['name', 'theses__id']);
        $this->assertEquals("SELECT DISTINCT `test_note`.`name` as `name`, `test_note_thesis`.`id` as `theses__id`, `test_note_thesis`.`name` AS `order__theses__name` FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` ORDER BY `order__theses__name` DESC", $sql);
    }
    
    public function testRaw()
    {
        $note1 = new Note();
        $note1->name = 'First note';
        $note1->save();

        $note2 = new Note();
        $note2->name = 'Second note';
        $note2->save();
        
        $this->assertEquals([
            [
                'id' => '2',
                'name' => 'Second note'
            ]
        ], Note::objects()->raw("SELECT * FROM test_note WHERE id = :id", ['id' => $note2->id]));

        $rawAll = Note::objects()->rawAll("SELECT * FROM test_note ORDER BY id");

        $this->assertEquals(2, count($rawAll));
        $this->assertInstanceOf(Note::class, $rawAll[0]);
        $this->assertEquals(1, $rawAll[0]->id);

        $rawGet = Note::objects()->rawGet("SELECT * FROM test_note ORDER BY id DESC");

        $this->assertInstanceOf(Note::class, $rawGet);
        $this->assertEquals(2, $rawGet->id);
    }

    public function testRelationAlias()
    {
        $note1 = new Note();
        $note1->name = 'First note';
        $note1->save();

        $thesis1 = new NoteThesis();
        $thesis1->note = $note1;
        $thesis1->name = 'First thesis';
        $thesis1->save();

        $thesis2 = new NoteThesis();
        $thesis2->note = $note1;
        $thesis2->name = 'Second thesis';
        $thesis2->save();

        $qs = Note::objects()->filter([
            "theses#1__id" => $thesis1->id, "theses#1__name" => $thesis1->name,
            "theses#2__id" => $thesis2->id, "theses#2__name" => $thesis2->name
        ]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_thesis` ON `test_note`.`id` = `test_note_thesis`.`note_id` LEFT JOIN `test_note_thesis` AS `test_note_thesis_1` ON `test_note`.`id` = `test_note_thesis_1`.`note_id` WHERE `test_note_thesis`.`id` = 1 AND `test_note_thesis`.`name` = 'First thesis' AND `test_note_thesis_1`.`id` = 2 AND `test_note_thesis_1`.`name` = 'Second thesis' GROUP BY `test_note`.`id`", $sql);
        $this->assertEquals(1, count($qs->all()));
    }

    public function testDynamicProperties()
    {
        $note1 = new Note();
        $note1->name = 'First note';
        $note1->save();

        $note2 = new Note();
        $note2->name = 'Second note';
        $note2->save();

        $property1 = new NoteProperty();
        $property1->name = 'Description';
        $property1->type = NoteProperty::TYPE_CHAR;
        $property1->save();

        $property2 = new NoteProperty();
        $property2->name = 'Rating';
        $property2->type = NoteProperty::TYPE_INT;
        $property2->save();

        $value1 = new NotePropertyCharValue();
        $value1->note = $note1;
        $value1->note_property = $property1;
        $value1->value = 'Some description for first note';
        $value1->save();

        $value2 = new NotePropertyIntValue();
        $value2->note = $note2;
        $value2->note_property = $property2;
        $value2->value = 4;
        $value2->save();

        $qs = Note::objects()->getQuerySet();
        $qs->appendRelation("property#1", new NotePropertyCharValue(), [
            [
                'table' => NotePropertyCharValue::getTableName(),
                'from' => 'id',
                'to' => 'note_id'
            ]
        ]);
        $this->assertEquals(true, $qs->hasRelation('property#1'));
        $this->assertEquals(false, $qs->hasRelation('property#2'));
        $qs = $qs->filter([
            'property#1__note_property_id' => $property1->id,
            'property#1__value' => "Some description for first note"
        ]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_property_char_value` ON `test_note`.`id` = `test_note_property_char_value`.`note_id` WHERE `test_note_property_char_value`.`note_property_id` = 1 AND `test_note_property_char_value`.`value` = 'Some description for first note'", $sql);
        $this->assertEquals(1, count($qs->all()));

        $qs->appendRelation("property#2", new NotePropertyIntValue(), [
            [
                'table' => NotePropertyIntValue::getTableName(),
                'from' => 'id',
                'to' => 'note_id'
            ]
        ]);
        $this->assertEquals(true, $qs->hasRelation('property#1'));
        $this->assertEquals(true, $qs->hasRelation('property#2'));
        $qs = $qs->filter([
            'property#2__note_property_id' => $property2->id,
            'property#2__value' => 3
        ]);

        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_property_char_value` ON `test_note`.`id` = `test_note_property_char_value`.`note_id` LEFT JOIN `test_note_property_int_value` ON `test_note`.`id` = `test_note_property_int_value`.`note_id` WHERE (`test_note_property_char_value`.`note_property_id` = 1 AND `test_note_property_char_value`.`value` = 'Some description for first note') AND (`test_note_property_int_value`.`note_property_id` = 2 AND `test_note_property_int_value`.`value` = 3)", $sql);
        $this->assertEquals(0, count($qs->all()));

        $qs->appendRelation("property#3", new NotePropertyIntValue(), [
            [
                'table' => NotePropertyIntValue::getTableName(),
                'from' => 'id',
                'to' => 'note_id'
            ]
        ]);
        $this->assertEquals(true, $qs->hasRelation('property#1'));
        $this->assertEquals(true, $qs->hasRelation('property#2'));
        $this->assertEquals(true, $qs->hasRelation('property#3'));
        $qs = $qs->filter([
            'property#3__note_property_id' => 10,
            'property#3__value' => 10
        ]);

        $sql = $qs->allSql();
        $this->assertEquals("SELECT `test_note`.* FROM `test_note` LEFT JOIN `test_note_property_char_value` ON `test_note`.`id` = `test_note_property_char_value`.`note_id` LEFT JOIN `test_note_property_int_value` ON `test_note`.`id` = `test_note_property_int_value`.`note_id` LEFT JOIN `test_note_property_int_value` AS `test_note_property_int_value_1` ON `test_note`.`id` = `test_note_property_int_value_1`.`note_id` WHERE (`test_note_property_char_value`.`note_property_id` = 1 AND `test_note_property_char_value`.`value` = 'Some description for first note') AND (`test_note_property_int_value`.`note_property_id` = 2 AND `test_note_property_int_value`.`value` = 3) AND (`test_note_property_int_value_1`.`note_property_id` = 10 AND `test_note_property_int_value_1`.`value` = 10)", $sql);
        $this->assertEquals(0, count($qs->all()));
    }
}