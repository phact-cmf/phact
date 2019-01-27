<?php
/**
 *
 *
 * All rights {$q}reserved{$q}.
 *
 * @author Okulov Anton
 * @email qantus@{$q}mail{$q}.{$q}ru{$q}
 * @version {$q}1{$q}.{$q}0{$q}
 * @date 10/04/16 10:14
 */

namespace Phact\Tests\Cases\Orm\Abs;

use Modules\Test\Models\Area;
use Modules\Test\Models\Author;
use Modules\Test\Models\Group;
use Modules\Test\Models\Note;
use Modules\Test\Models\NoteProperty;
use Modules\Test\Models\NotePropertyCharValue;
use Modules\Test\Models\NotePropertyIntValue;
use Modules\Test\Models\NoteThesis;
use Modules\Test\Models\NoteThesisVote;
use Phact\Orm\Aggregations\Avg;
use Phact\Orm\Aggregations\Count;
use Phact\Orm\Expression;
use Phact\Orm\Having\Having;
use Phact\Orm\Manager;
use Phact\Orm\Q;
use Phact\Orm\QuerySet;
use Phact\Tests\Templates\DatabaseTest;

abstract class AbstractQuerySetTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Note(),
            new NoteThesis(),
            new NoteThesisVote(),
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

    public function testExpressions()
    {
        $q = $this->getQuoteCharacter();
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['id__gt' => new Expression("{id} + {theses__id}"), new Expression("{id} <= {theses__id}")]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE ({$q}test_note{$q}.{$q}id{$q} > {$q}test_note{$q}.{$q}id{$q} + {$q}test_note_thesis_1{$q}.{$q}id{$q}) AND ({$q}test_note{$q}.{$q}id{$q} <= {$q}test_note_thesis_1{$q}.{$q}id{$q})", $sql);
        $this->assertEquals([], $qs->all());

        $qs = Note::objects()->getQuerySet();
        $qs->filter([new Expression("{id} + {theses__id} > ?", [2000])]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE {$q}test_note{$q}.{$q}id{$q} + {$q}test_note_thesis_1{$q}.{$q}id{$q} > 2000", $sql);
        $this->assertEquals([], $qs->all());

        $qs = Note::objects()->getQuerySet();
        $sql = $qs->valuesSql(['id', 's' => new Expression("({id} + {theses__id})")]);
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.{$q}id{$q} AS {$q}id{$q}, ({$q}test_note{$q}.{$q}id{$q} + {$q}test_note_thesis_1{$q}.{$q}id{$q}) AS {$q}s{$q} FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q}", $sql);
        $this->assertEquals([], $qs->values(['id', 's' => new Expression("({id} + {theses__id})")]));
    }

    public function testCondition()
    {
        $q = $this->getQuoteCharacter();
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['name' => 'Test', 'id__gte' => 10, 'theses__id__lte' => 5, Q::orQ(['id' => 20], ['name' => 'Layla'])]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE ({$q}test_note{$q}.{$q}name{$q} = 'Test') AND ({$q}test_note{$q}.{$q}id{$q} >= 10) AND ({$q}test_note_thesis_1{$q}.{$q}id{$q} <= 5) AND (({$q}test_note{$q}.{$q}id{$q} = 20) OR ({$q}test_note{$q}.{$q}name{$q} = 'Layla'))", $sql);
    }

    public function testExclude()
    {
        $q = $this->getQuoteCharacter();
        $qs = Note::objects()->getQuerySet();
        $qs->exclude(['name' => 'Test']);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE NOT ({$q}test_note{$q}.{$q}name{$q} = 'Test')", $sql);
    }

    public function testExcludeFilter()
    {
        $q = $this->getQuoteCharacter();
        $qs = Note::objects()->getQuerySet();
        $qs->filter(['name' => 'Actual']);
        $qs->exclude(['name' => 'Test']);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE ({$q}test_note{$q}.{$q}name{$q} = 'Actual') AND (NOT ({$q}test_note{$q}.{$q}name{$q} = 'Test'))", $sql);
    }

    public function testOrder()
    {
        $q = $this->getQuoteCharacter();
        $qs = Note::objects()->getQuerySet();
        $qs->order(['-id', new Expression('{id} = ? ASC', [0])]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} ORDER BY {$q}test_note{$q}.{$q}id{$q} DESC, {$q}test_note{$q}.{$q}id{$q} = 0 ASC", $sql);
    }

    public function testSubQuery()
    {
        $q = $this->getQuoteCharacter();
        $qs = Note::objects()->getQuerySet();
        $qs->filter([
            'id__in' => NoteThesis::objects()->filter(['id__gt' => 20])->select(['note_id'])
        ]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} IN (SELECT note_id FROM {$q}test_note_thesis{$q} WHERE {$q}test_note_thesis{$q}.{$q}id{$q} > 20)", $sql);

        $qs = Note::objects()->getQuerySet();
        $qs->filter([
            'id__gt' => NoteThesis::objects()->filter(['id__gt' => new Expression("{$q}test_note{$q}.{$q}id{$q}")])->select([new Expression('COUNT(*)')])
        ]);
        $sql = $qs->allSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} > (SELECT COUNT(*) FROM {$q}test_note_thesis{$q} WHERE {$q}test_note_thesis{$q}.{$q}id{$q} > {$q}test_note{$q}.{$q}id{$q})", $sql);
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
        $q = $this->getQuoteCharacter();
        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->limit(1)->allSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 3 LIMIT 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['id' => 3])->limit(1)->offset(2)->allSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 3 LIMIT 1 OFFSET 2", $sql);
    }

    public function testAggregations()
    {
        $q = $this->getQuoteCharacter();
        $sql = Note::objects()->getQuerySet()->aggregateSql(new Count());
        $this->assertEquals("SELECT COUNT(*) as aggregation FROM {$q}test_note{$q}", $sql);

        $sql = Note::objects()->getQuerySet()->aggregateSql(new Avg('theses__id'));
        $this->assertEquals("SELECT AVG({$q}test_note_thesis_1{$q}.{$q}id{$q}) as aggregation FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q}", $sql);
    }

    public function testGet()
    {
        $q = $this->getQuoteCharacter();
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->getSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);
    }

    public function testPk()
    {
        $q = $this->getQuoteCharacter();
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->getSql();
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['theses__pk__in' => [1,2]])->allSql();
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE {$q}test_note_thesis_1{$q}.{$q}id{$q} IN (1,2)", $sql);
    }

    public function testUpdate()
    {
        $q = $this->getQuoteCharacter();
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->updateSql(['name' => 'Test']);
        $this->assertEquals("UPDATE test_note SET name = 'Test' WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->updateSql(['name' => 'Test']);
        $this->assertEquals("UPDATE test_note SET name = 'Test' WHERE {$q}test_note{$q}.{$q}id{$q} IN (SELECT {$q}temp_table_wrapper{$q}.{$q}id{$q} FROM (SELECT {$q}test_note{$q}.{$q}id{$q} FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE ({$q}test_note{$q}.{$q}id{$q} = 1) AND ({$q}test_note_thesis_1{$q}.{$q}id{$q} IN (1,2))) AS temp_table_wrapper)", $sql);
    }

    public function testDelete()
    {
        $q = $this->getQuoteCharacter();
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->deleteSql();
        $this->assertEquals("DELETE FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1, 'theses__pk__in' => [1,2]])->deleteSql();
        $this->assertEquals("DELETE FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} IN (SELECT {$q}temp_table_wrapper{$q}.{$q}id{$q} FROM (SELECT {$q}test_note{$q}.{$q}id{$q} FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE ({$q}test_note{$q}.{$q}id{$q} = 1) AND ({$q}test_note_thesis_1{$q}.{$q}id{$q} IN (1,2))) AS temp_table_wrapper)", $sql);
    }

    public function testValues()
    {
        $q = $this->getQuoteCharacter();
        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name']);
        $this->assertEquals("SELECT {$q}test_note{$q}.{$q}id{$q} AS {$q}id{$q}, {$q}test_note{$q}.{$q}name{$q} AS {$q}name{$q} FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name', 'theses__name']);
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.{$q}id{$q} AS {$q}id{$q}, {$q}test_note{$q}.{$q}name{$q} AS {$q}name{$q}, {$q}test_note_thesis_1{$q}.{$q}name{$q} AS {$q}theses__name{$q} FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name'], true);
        $this->assertEquals("SELECT {$q}test_note{$q}.{$q}id{$q} AS {$q}id{$q}, {$q}test_note{$q}.{$q}name{$q} AS {$q}name{$q} FROM {$q}test_note{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);

        $sql = Note::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'name', 'theses__name'], false, false);
        $this->assertEquals("SELECT {$q}test_note{$q}.{$q}id{$q} AS {$q}id{$q}, {$q}test_note{$q}.{$q}name{$q} AS {$q}name{$q}, {$q}test_note_thesis_1{$q}.{$q}name{$q} AS {$q}theses__name{$q} FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} WHERE {$q}test_note{$q}.{$q}id{$q} = 1", $sql);

        $sql = NoteThesisVote::objects()->getQuerySet()->filter(['pk' => 1])->valuesSql(['id', 'rating', 'note_thesis__note__name'], false, false);
        $this->assertEquals("SELECT {$q}test_note_thesis_vote{$q}.{$q}id{$q} AS {$q}id{$q}, {$q}test_note_thesis_vote{$q}.{$q}rating{$q} AS {$q}rating{$q}, {$q}test_note_2{$q}.{$q}name{$q} AS {$q}note_thesis__note__name{$q} FROM {$q}test_note_thesis_vote{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note_thesis_vote{$q}.{$q}note_thesis_id{$q} = {$q}test_note_thesis_1{$q}.{$q}id{$q} LEFT JOIN {$q}test_note{$q} {$q}test_note_2{$q} ON {$q}test_note_thesis_1{$q}.{$q}note_id{$q} = {$q}test_note_2{$q}.{$q}id{$q} WHERE {$q}test_note_thesis_vote{$q}.{$q}id{$q} = 1", $sql);
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
        $q = $this->getQuoteCharacter();
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
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.*, COUNT({$q}test_note_thesis_1{$q}.{$q}id{$q}) as _service__having FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} GROUP BY {$q}test_note{$q}.{$q}id{$q} HAVING COUNT({$q}test_note_thesis_1{$q}.{$q}id{$q}) >= 1", $sql);

        $all = Note::objects()->getQuerySet()->having(new Having(new Count('theses__id'), '>= 1'))->all();
        $this->assertEquals(1, count($all));

        $this->assertEquals("First note", $all[0]->name);
    }

    public function testOrderWithMany()
    {
        $q = $this->getQuoteCharacter();
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
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.*, {$q}test_note_thesis_1{$q}.{$q}name{$q} AS {$q}_service__order__theses__name{$q} FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} ORDER BY {$q}_service__order__theses__name{$q} DESC", $sql);
        $this->assertCount(2, $qs->all());

        $sql = $qs->valuesSql(['name', 'theses__id']);
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.{$q}name{$q} AS {$q}name{$q}, {$q}test_note_thesis_1{$q}.{$q}id{$q} AS {$q}theses__id{$q}, {$q}test_note_thesis_1{$q}.{$q}name{$q} AS {$q}_service__order__theses__name{$q} FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} ORDER BY {$q}_service__order__theses__name{$q} DESC", $sql);
        $this->assertEquals('First note', $qs->values(['name', 'theses__id'])[0]['name']);
    }
    
    public function testRaw()
    {
        $q = $this->getQuoteCharacter();
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
        ], Note::objects()->raw("SELECT * FROM {$q}test_note{$q} WHERE id = :id", ['id' => $note2->id]));

        $rawAll = Note::objects()->rawAll("SELECT * FROM {$q}test_note{$q} ORDER BY id");

        $this->assertEquals(2, count($rawAll));
        $this->assertInstanceOf(Note::class, $rawAll[0]);
        $this->assertEquals(1, $rawAll[0]->id);

        $rawGet = Note::objects()->rawGet("SELECT * FROM {$q}test_note{$q} ORDER BY id DESC");

        $this->assertInstanceOf(Note::class, $rawGet);
        $this->assertEquals(2, $rawGet->id);
    }

    public function testRelationAlias()
    {
        $q = $this->getQuoteCharacter();
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
        $this->assertEquals("SELECT DISTINCT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_1{$q}.{$q}note_id{$q} LEFT JOIN {$q}test_note_thesis{$q} {$q}test_note_thesis_2{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_thesis_2{$q}.{$q}note_id{$q} WHERE ({$q}test_note_thesis_1{$q}.{$q}id{$q} = 1) AND ({$q}test_note_thesis_1{$q}.{$q}name{$q} = 'First thesis') AND ({$q}test_note_thesis_2{$q}.{$q}id{$q} = 2) AND ({$q}test_note_thesis_2{$q}.{$q}name{$q} = 'Second thesis')", $sql);
        $this->assertEquals(1, count($qs->all()));
    }

    public function testDynamicProperties()
    {
        $q = $this->getQuoteCharacter();
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
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_property_char_value{$q} {$q}test_note_property_char_value_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_property_char_value_1{$q}.{$q}note_id{$q} WHERE ({$q}test_note_property_char_value_1{$q}.{$q}note_property_id{$q} = 1) AND ({$q}test_note_property_char_value_1{$q}.{$q}value{$q} = 'Some description for first note')", $sql);
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
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_property_char_value{$q} {$q}test_note_property_char_value_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_property_char_value_1{$q}.{$q}note_id{$q} LEFT JOIN {$q}test_note_property_int_value{$q} {$q}test_note_property_int_value_2{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_property_int_value_2{$q}.{$q}note_id{$q} WHERE (({$q}test_note_property_char_value_1{$q}.{$q}note_property_id{$q} = 1) AND ({$q}test_note_property_char_value_1{$q}.{$q}value{$q} = 'Some description for first note')) AND (({$q}test_note_property_int_value_2{$q}.{$q}note_property_id{$q} = 2) AND ({$q}test_note_property_int_value_2{$q}.{$q}value{$q} = 3))", $sql);
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
        $this->assertEquals("SELECT {$q}test_note{$q}.* FROM {$q}test_note{$q} LEFT JOIN {$q}test_note_property_char_value{$q} {$q}test_note_property_char_value_1{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_property_char_value_1{$q}.{$q}note_id{$q} LEFT JOIN {$q}test_note_property_int_value{$q} {$q}test_note_property_int_value_2{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_property_int_value_2{$q}.{$q}note_id{$q} LEFT JOIN {$q}test_note_property_int_value{$q} {$q}test_note_property_int_value_3{$q} ON {$q}test_note{$q}.{$q}id{$q} = {$q}test_note_property_int_value_3{$q}.{$q}note_id{$q} WHERE (({$q}test_note_property_char_value_1{$q}.{$q}note_property_id{$q} = 1) AND ({$q}test_note_property_char_value_1{$q}.{$q}value{$q} = 'Some description for first note')) AND (({$q}test_note_property_int_value_2{$q}.{$q}note_property_id{$q} = 2) AND ({$q}test_note_property_int_value_2{$q}.{$q}value{$q} = 3)) AND (({$q}test_note_property_int_value_3{$q}.{$q}note_property_id{$q} = 10) AND ({$q}test_note_property_int_value_3{$q}.{$q}value{$q} = 10))", $sql);
        $this->assertEquals(0, count($qs->all()));
    }
}