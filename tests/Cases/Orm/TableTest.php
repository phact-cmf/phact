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

use Phact\Orm\TableManager;

class TableTest extends DatabaseTest
{
    public function testCreate()
    {
        $tableManager = new TableManager();
        $tableManager->create([
            new Note()
        ]);
    }
}