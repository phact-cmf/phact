<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 12/04/16 18:02
 */

namespace Phact\Orm;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\SmartProperties;

class ConnectionManager implements ConnectionManagerInterface
{
    use SmartProperties;

    protected $_connections;
    protected $_connectionsConfig;

    public $defaultConnection = 'default';

    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }

    public function setConnections($config = [])
    {
        $this->_connectionsConfig = $config;
    }

    /**
     * @param null $name
     * @return \Doctrine\DBAL\Connection
     * @throws UnknownPropertyException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getConnection($name = null): Connection
    {
        if (!$name) {
            $name = $this->getDefaultConnection();
        }
        if (!isset($this->_connections[$name])) {
            if (isset($this->_connectionsConfig[$name])) {
                $config = $this->_connectionsConfig[$name];
                /** @var Connection $connection */
                $connection = DriverManager::getConnection($config, new Configuration());
                $this->_connections[$name] = $connection;
            } else {
                throw new UnknownPropertyException("Connection with name " . $name . " not found");
            }
        }
        return $this->_connections[$name];
    }
}