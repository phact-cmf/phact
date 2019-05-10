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

use Modules\Test\Models\Musician;
use Modules\Test\Models\Song;
use Phact\Tests\Templates\DatabaseTest;

abstract class AbstractManyManagerTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Song(),
            new Musician()
        ];
    }

    public function fillData()
    {
        $musicianFFDP = new Musician();
        $musicianFFDP->name = 'Five Finger Death Punch';
        $musicianFFDP->save();

        $musicianPL = new Musician();
        $musicianPL->name = 'Paradise Lost';
        $musicianPL->save();

        $song1 = new Song();
        $song1->musician = $musicianPL;
        $song1->name = 'Solitary One';
        $song1->save();

        $song2 = new Song();
        $song2->musician = $musicianFFDP;
        $song2->name = 'Wrong Side of Heaven';
        $song2->save();

        $song3 = new Song();
        $song3->musician = $musicianFFDP;
        $song3->name = 'Blue on Black';
        $song3->save();

        $song4 = new Song();
        $song4->musician = $musicianPL;
        $song4->name = 'Tragic Idol';
        $song4->save();
    }

    public function testClean()
    {
        $this->fillData();
        $musicianFFDP = Musician::objects()->filter(['name' => 'Five Finger Death Punch'])->get();

        $data = $musicianFFDP->songs->byName()->all();
        $this->assertCount(2, $data, 'Incorrect count of result');
        $this->assertEquals('Blue on Black', $data[0]->name, 'Incorrect name of first result');
        $this->assertEquals('Wrong Side of Heaven', $data[1]->name, 'Incorrect name of second result');
    }

    public function testWith()
    {
        $this->fillData();
        $musicians = Musician::objects()->order(['name'])->with(['songs->byName'])->all();

        $this->assertCount(2, $musicians[0]->getWithData('songs->byName'));

        $data = $musicians[0]->songs->byName()->all();

        $this->assertCount(2, $data, 'Incorrect count of result');
        $this->assertEquals('Blue on Black', $data[0]->name, 'Incorrect name of first result');
        $this->assertEquals('Wrong Side of Heaven', $data[1]->name, 'Incorrect name of second result');
    }
}