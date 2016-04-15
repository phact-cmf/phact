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

class SaveTest extends DatabaseTest
{
    public function testInsert()
    {
        $note = new Note();
        $note->name = null;
//        var_dump($note);die();
        $note->save();
    }
}