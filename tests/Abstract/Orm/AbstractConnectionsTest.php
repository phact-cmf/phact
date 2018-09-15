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
use Phact\Orm\ConnectionManager;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\DBAL\Connection as DBALConnection;

abstract class AbstractConnectionsTest extends DatabaseTest
{
    public function testSimple()
    {
        /** @var ConnectionManager $manager */
        $manager = Phact::app()->db;
        /** @var DBALConnection $connection */
        $connection = $manager->getConnection();
        $queryBuilder = $connection->createQueryBuilder();

        $this->assertInstanceOf(ConnectionManager::class, $manager);
        $this->assertInstanceOf(DBALConnection::class, $connection);
        $this->assertInstanceOf(DBALQueryBuilder::class, $queryBuilder);
    }
}