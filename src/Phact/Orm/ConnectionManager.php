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
use Phact\Di\Container;
use Phact\Di\ContainerInterface;
use Phact\Exceptions\DependencyException;
use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\SmartProperties;

class ConnectionManager implements ConnectionManagerInterface
{
    use SmartProperties;

    protected $_connections;
    protected $_connectionsConfig;

    public $defaultConnection = 'default';

    /**
     * @var ContainerInterface|null
     */
    private $container;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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
     * @throws InvalidConfigException
     * @throws DependencyException
     */
    public function getConnection($name = null): Connection
    {
        if (!$name) {
            $name = $this->getDefaultConnection();
        }
        if (!isset($this->_connections[$name])) {
            if (isset($this->_connectionsConfig[$name])) {
                $params = $this->_connectionsConfig[$name];

                $configuration = new Configuration();
                if (isset($params['configuration'])) {
                    $configuration = $this->retrieveConfiguration($params['configuration']);
                    unset($params['configuration']);
                }

                /** @var Connection $connection */
                $connection = DriverManager::getConnection($params, $configuration);
                $this->_connections[$name] = $connection;
            } else {
                throw new UnknownPropertyException("Connection with name '{$name}' not found");
            }
        }
        return $this->_connections[$name];
    }

    /**
     * @param $name
     * @return Configuration
     * @throws InvalidConfigException
     * @throws DependencyException
     */
    public function retrieveConfiguration($name): Configuration
    {
        if (!$this->container) {
            throw new DependencyException(sprintf('Dependency %s is not loaded', Container::class));
        }
        $name = ltrim($name, '@');
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }
        throw new InvalidConfigException("Count not find connection configuration by name '{$name}'");
    }
}