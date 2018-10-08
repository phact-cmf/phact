<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 10/04/16 10:59
 */

namespace Phact\Tests;

use Phact\Main\Phact;
use Phact\Orm\ConnectionManager;
use Phact\Orm\TableManager;

class DatabaseTest extends AppTest
{
    protected $defaultConnection = 'default';

    public function setUp()
    {
        parent::setUp();

        $connections = $this->getConnections();
        $connectionManager = new ConnectionManager();
        if (!isset($connections[$this->defaultConnection])) {
            $this->markTestSkipped('There is no connection '. $this->defaultConnection);
        }
        $connectionManager->setConnections([
            'default' => $connections[$this->defaultConnection]
        ]);
        Phact::app()->setComponent('db', $connectionManager);

        $tableManager = new TableManager();
        $models = $this->useModels();
        if ($models) {
            $tableManager->create($models);
        }
    }

    public function tearDown()
    {
        parent::tearDown();
        $tableManager = new TableManager();
        $models = $this->useModels();
        if ($models) {
            $tableManager->drop($models);
        }
    }

    public function useModels()
    {
        return [];
    }
    
    public function getConnections()
    {
        $dir = implode(DIRECTORY_SEPARATOR,[__DIR__, '..', 'config']);
        $local = implode(DIRECTORY_SEPARATOR,[$dir, 'connections_local.php']);
        $public = implode(DIRECTORY_SEPARATOR,[$dir, 'connections.php']);
        if (is_file($local)) {
            return require($local);
        } elseif (is_file($public)) {
            return require($public);
        }
        return [];
    }
}