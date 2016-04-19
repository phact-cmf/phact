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
use Modules\Test\Models\NoteThesis;

class SaveTest extends DatabaseTest
{
    public function testInsert()
    {
        $note = new Note();
        $note->name = "Test";
        $note->save();

        $noteThesis = new NoteThesis();
        $noteThesis->name = 'Test note thesis';
        $noteThesis->note = $note;
        $noteThesis->save();

//        $this->assertInstanceOf(Note::class, $noteThesis->note);
    }
}