<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/05/19 10:44
 */

namespace Phact\Tests\Cases\Orm\Abs;

use Modules\Test\Models\Author;
use Modules\Test\Models\Book;
use Modules\Test\Models\Musician;
use Modules\Test\Models\Note;
use Modules\Test\Models\NoteThesis;
use Modules\Test\Models\NoteThesisVote;
use Modules\Test\Models\Song;
use Phact\Tests\Templates\DatabaseTest;

abstract class AbstractWithBenchmarkTest extends DatabaseTest
{
    public function useModels()
    {
        return [
            new Song(),
            new Musician(),
            new Note(),
            new NoteThesis(),
            new NoteThesisVote(),
            new Author(),
            new Book(),
        ];
    }

    public function testNamedSelection()
    {
        $this->markTestSkipped();
        for ($i = 0;$i < 20;$i++) {
            $musician = new Musician();
            $musician->setAttributes([
                'name' => $this->randomName()
            ]);
            $musician->save();

            $songsCount = 9;
            for ($j = 0;$j < $songsCount;$j++) {
                $song = new Song();
                $song->setAttributes([
                    'name' => $this->randomName(),
                    'musician_id' => $musician->id
                ]);
                $song->save();
            }
        }

        $musiciansCountRaw = 0;
        $songsCountRaw = 0;

        $musiciansCountWith = 0;
        $songsCountWith = 0;

        $start = microtime(true);
        $musicians = Musician::objects()->all();
        $musiciansCountRaw = count($musicians);
        foreach ($musicians as $musician) {
            $songs = $musician->songs->byName()->all();
            $songsCountRaw += count($songs);
        }
        $endRaw = microtime(true) - $start;

        $start = microtime(true);
        $musicians = Musician::objects()->with(['songs->byName'])->all();
        $musiciansCountWith = count($musicians);
        foreach ($musicians as $musician) {
            $songs = $musician->songs->byName()->all();
            $songsCountWith += count($songs);
        }
        $endWith = microtime(true) - $start;

        $this->assertEquals($musiciansCountRaw, $musiciansCountWith);
        $this->assertEquals($songsCountRaw, $songsCountWith);
        $this->output("Fetch named has many - $musiciansCountRaw / $songsCountWith", $endRaw, $endWith);

        $start = microtime(true);
        $songs = Song::objects()->all();
        $songsCountRaw = count($songs);
        foreach ($songs as $song) {
            $musician = $song->musician;
        }
        $endRaw = microtime(true) - $start;

        $start = microtime(true);
        $songs = Song::objects()->with(['musician'])->all();
        $songsCountWith = count($songs);
        foreach ($songs as $song) {
            $musician = $song->musician;
        }
        $endWith = microtime(true) - $start;

        $this->assertEquals($songsCountRaw, $songsCountWith);
        $this->output("Fetch fk - $songsCountWith", $endRaw, $endWith);
    }

    public function test2Levels()
    {
        $this->markTestSkipped();
        for ($i = 0;$i < 50;$i++) {
            $note = new Note();
            $note->setAttributes([
                'name' => $this->randomName()
            ]);
            $note->save();

            $count = random_int(10, 20);
            for ($j = 0;$j < $count;$j++) {
                $thesis = new NoteThesis();
                $thesis->setAttributes([
                    'name' => $this->randomName(),
                    'note_id' => $note->id
                ]);
                $thesis->save();

                $countIn = random_int(9, 10);
                for ($k = 0;$k < $countIn;$k++) {
                    $vote = new NoteThesisVote();
                    $vote->setAttributes([
                        'rating' => random_int(1, 5),
                        'note_thesis_id' => $thesis->id
                    ]);
                    $vote->save();
                }
            }
        }

        $level1CountRaw = 0;
        $level2CountRaw = 0;
        $level3CountRaw = 0;

        $level1CountWith = 0;
        $level2CountWith = 0;
        $level3CountWith = 0;

        $start = microtime(true);
        $level1All = Note::objects()->all();
        foreach ($level1All as $level1Item) {
            $level1CountRaw++;
            foreach ($level1Item->theses->all() as $level2Item) {
                $level2CountRaw++;
                $level3CountRaw += count($level2Item->votes->all());
            }
        }
        $endRaw = microtime(true) - $start;

        $start = microtime(true);
        $level1All = Note::objects()->with(['theses__votes'])->all();
        foreach ($level1All as $level1Item) {
            $level1CountWith++;
            foreach ($level1Item->theses->all() as $level2Item) {
                $level2CountWith++;
                $level3CountWith += count($level2Item->votes->all());
            }
        }
        $endWith = microtime(true) - $start;

        $this->assertEquals($level1CountRaw, $level1CountWith);
        $this->assertEquals($level2CountRaw, $level2CountWith);
        $this->assertEquals($level3CountRaw, $level3CountWith);
        $this->output("Fetch has many by has many - $level1CountRaw / $level2CountRaw / $level3CountRaw", $endRaw, $endWith);

        $start = microtime(true);
        $level1All = NoteThesis::objects()->all();
        foreach ($level1All as $level1Item) {
            $votes = $level1Item->votes->all();
            $note = $level1Item->note;
        }
        $endRaw = microtime(true) - $start;

        $start = microtime(true);
        $level1All = NoteThesis::objects()->with(['note', 'votes'])->all();
        foreach ($level1All as $level1Item) {
            $votes = $level1Item->votes->all();
            $note = $level1Item->note;
        }
        $endWith = microtime(true) - $start;

        $this->output("Fetch has many and fk - $level2CountRaw / $level3CountRaw", $endRaw, $endWith);

        $start = microtime(true);
        $level1All = NoteThesisVote::objects()->all();
        foreach ($level1All as $level1Item) {
            $noteThesis = $level1Item->note_thesis;
            $note = $noteThesis->note;
        }
        $endRaw = microtime(true) - $start;

        $start = microtime(true);
        $level1All = NoteThesisVote::objects()->with(['note_thesis__note'])->all();
        foreach ($level1All as $level1Item) {
            $noteThesis = $level1Item->note_thesis;
            $note = $noteThesis->note;
        }
        $endWith = microtime(true) - $start;

        $this->output("Fetch fk by fk - $level3CountRaw", $endRaw, $endWith);
    }

    public function testM2M()
    {
        $this->markTestSkipped();
        for ($i = 0; $i < 1000; $i++) {
            $author = new Author();
            $author->setAttributes([
                'name' => $this->randomName()
            ]);
            $author->save();

            $booksCount = random_int(3, 5);
            for ($j = 0; $j < $booksCount; $j++) {
                $book = new Book();
                $book->name = $this->randomName();
                $book->authors = [$author];
                $book->save();
            }
        }

        $level1CountRaw = 0;
        $level2CountRaw = 0;

        $level1CountWith = 0;
        $level2CountWith = 0;

        $start = microtime(true);
        $level1All = Author::objects()->all();
        foreach ($level1All as $level1Item) {
            $level1CountRaw++;
            foreach ($level1Item->books->all() as $level2Item) {
                $level2CountRaw++;
            }
        }
        $endRaw = microtime(true) - $start;

        $start = microtime(true);
        $level1All = Author::objects()->with(['books'])->all();
        foreach ($level1All as $level1Item) {
            $level1CountWith++;
            foreach ($level1Item->books->all() as $level2Item) {
                $level2CountWith++;
            }
        }
        $endWith = microtime(true) - $start;

        $this->assertEquals($level1CountRaw, $level1CountWith);
        $this->assertEquals($level2CountRaw, $level2CountWith);
        $this->output("Fetch many to many  - $level1CountRaw / $level2CountRaw", $endRaw, $endWith);
    }

    public function output($message, $rawResult, $withResult)
    {
        echo "Test: $message" . PHP_EOL;
        echo 'Raw result: ' . number_format($rawResult, 6, '.', ' ') . PHP_EOL;
        echo 'With result: ' . number_format($withResult, 6, '.', ' ') . PHP_EOL;
        $result = 'Win';
        $resultData = $rawResult / $withResult;
        if ($rawResult < $withResult) {
            $result = 'Fail';
            $resultData = $withResult/$rawResult;
        }
        $result .= ' ' . number_format($resultData * 100, 2) . '%';
        echo  $result . PHP_EOL . PHP_EOL;
    }

    public function randomName($length = 12): string
    {
        return bin2hex(random_bytes($length));
    }
}