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

namespace Phact\Tests\Cases\Orm\Pgsql\Bench;

use Phact\Tests\Cases\Orm\Abs\AbstractWithBenchmarkTest;

class PgsqlWithBenchmarkTest extends AbstractWithBenchmarkTest
{
    protected $defaultConnection = 'pgsql';
}