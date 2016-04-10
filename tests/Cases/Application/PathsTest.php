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
 * @date 10/04/16 08:21
 */

namespace Phact\Tests;


use Phact\Helpers\Paths;

class PathsTest extends TestCase
{
    public function testPaths()
    {
        $testPath =  implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'sandbox', 'app', 'Modules']);
        Paths::add('Modules',$testPath);

        $this->assertEquals(
            Paths::get('Modules'),
            $testPath
        );
        $this->assertEquals(
            Paths::get('Modules.Test'),
            implode(DIRECTORY_SEPARATOR, [$testPath, 'Test'])
        );
        $this->assertEquals(
            Paths::get('Modules.Test.Models'),
            implode(DIRECTORY_SEPARATOR, [$testPath, 'Test', 'Models'])
        );
        $this->assertNull(Paths::get('NotExistingPath'));
    }
}