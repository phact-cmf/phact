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

use Phact\Main\Phact;
use Phact\Orm\Adapters\Adapter;
use Phact\Orm\Connection;
use Phact\Orm\ConnectionManager;
use Phact\Orm\QueryBuilder;

class ConnectionsTest extends DatabaseTest
{
    public function testSimple()
    {
        $manager = Phact::app()->db;
        $connection = $manager->getConnection();
        $adapter = $connection->getAdapter();
        $queryBuilder = $connection->getQueryBuilder();

        $this->assertInstanceOf(ConnectionManager::class, $manager);
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertInstanceOf(Adapter::class, $adapter);
        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }
}