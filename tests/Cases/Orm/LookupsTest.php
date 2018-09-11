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

class LookupsTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Note(),
            new NoteThesis()
        ];
    }

    public function testEq()
    {
        $objects = $this->createObjects();
        $this->assertEquals($objects['thesises'][0]->id, NoteThesis::objects()->filter(['name' => 'First thesis'])->get()->id);
        $this->assertEquals($objects['thesises'][1]->id, NoteThesis::objects()->filter(['name__exact' => 'Second thesis'])->get()->id);
    }

    public function testGtLt()
    {
        $objects = $this->createObjects();
        $this->assertEquals(2, count(NoteThesis::objects()->filter(['id__gt' => 0])->all()));
        $this->assertEquals(2, count(NoteThesis::objects()->filter(['id__gte' => 1])->all()));


        $this->assertEquals(1, count(NoteThesis::objects()->filter(['id__lte' => 1])->all()));
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['id__lt' => 2])->all()));
    }

    public function testContains()
    {
        $objects = $this->createObjects();
        $this->assertEquals(2, count(NoteThesis::objects()->filter(['name__contains' => 'thesis'])->all()));
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['name__contains' => 'First'])->all()));
    }

    public function testIn()
    {
        $objects = $this->createObjects();
        $this->assertEquals(2, count(NoteThesis::objects()->filter(['name__in' => ['First thesis', 'Second thesis']])->all()));
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['id__in' => [1,10,100]])->all()));
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['id__in' => [2]])->all()));
    }

    public function testStartswith()
    {
        $objects = $this->createObjects();
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['name__startswith' => 'First'])->all()));
        $this->assertEquals(0, count(NoteThesis::objects()->filter(['name__startswith' => 'Another'])->all()));
    }

    public function testEndswith()
    {
        $objects = $this->createObjects();
        $this->assertEquals(2, count(NoteThesis::objects()->filter(['name__endswith' => 'thesis'])->all()));
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['name__endswith' => 't thesis'])->all()));
    }

    public function testRange()
    {
        $objects = $this->createObjects();
        $this->assertEquals(2, count(NoteThesis::objects()->filter(['id__range' => [0,3]])->all()));
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['id__range' => [2,10]])->all()));
    }

    public function testIsnull()
    {
        $objects = $this->createObjects();
        $this->assertEquals(0, count(NoteThesis::objects()->filter(['name__isnull' => true])->all()));
        $this->assertEquals(2, count(NoteThesis::objects()->filter(['name__isnull' => false])->all()));
    }

    public function testRegex()
    {
        $objects = $this->createObjects();
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['name__regex' => '^Fir.*$'])->all()));
        $this->assertEquals(1, count(NoteThesis::objects()->filter(['name__regex' => '^.+nd.+si.+$'])->allSql()));
    }

    public function createObjects()
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

        return [
            'note' => $note1->id,
            'thesises' => [
                $thesis1,
                $thesis2
            ]
        ];
    }
}