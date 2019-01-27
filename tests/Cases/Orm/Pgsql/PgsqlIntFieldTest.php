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

namespace Phact\Tests\Cases\Orm\Pgsql;

use Phact\Tests\Cases\Orm\Abs\AbstractIntFieldTest;

class PgsqlIntFieldTest extends AbstractIntFieldTest
{
    protected $defaultConnection = 'pgsql';
}