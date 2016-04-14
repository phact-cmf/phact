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

use Phact\Main\Phact;
use Phact\Orm\Connection;
use Phact\Orm\ConnectionManager;
use Pixie\QueryBuilder\QueryBuilderHandler;

class ConnectionsTest extends DatabaseTest
{
    public function testSimple()
    {
        $manager = Phact::app()->db;
        $connection = $manager->getConnection();
        $queryConnection = $connection->getQueryConnection();
        $queryBuilder = $connection->getQueryBuilder();

        $this->assertInstanceOf(ConnectionManager::class, $manager);
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertInstanceOf(\Pixie\Connection::class, $queryConnection);
        $this->assertInstanceOf(QueryBuilderHandler::class, $queryBuilder);
    }
}