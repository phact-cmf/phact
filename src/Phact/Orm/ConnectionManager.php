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
 * @date 12/04/16 18:02
 */

namespace Phact\Orm;

use Phact\Exceptions\UnknownPropertyException;
use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;

class ConnectionManager
{
    use SmartProperties;

    protected $_settings;
    protected $_connections;
    protected $_connectionsConfig;

    public $defaultConnection = 'default';

    public function setSettings($settings)
    {
        $this->_settings = $settings;
    }

    public function getCacheFieldsTimeout()
    {
        return isset($this->_settings['cacheFieldsTimeout']) ? $this->_settings['cacheFieldsTimeout'] : null;
    }

    public function getCacheQueriesTimeout()
    {
        return isset($this->_settings['cacheQueriesTimeout']) ? $this->_settings['cacheQueriesTimeout'] : null;
    }

    public function setConnections($config = [])
    {
        $this->_connectionsConfig = $config;
    }

    /**
     * @param null $name
     * @return \Phact\Orm\Connection
     * @throws UnknownPropertyException
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getConnection($name = null)
    {
        if (!$name) {
            $name = $this->defaultConnection;
        }
        if (!isset($this->_connections[$name])) {
            if (isset($this->_connectionsConfig[$name])) {
                $config = $this->_connectionsConfig[$name];
                if (!isset($config['class'])) {
                    $config['class'] = Connection::class;
                }
                $this->_connections[$name] = Configurator::create($config);
            } else {
                throw new UnknownPropertyException("Connection with name " . $name . " not found");
            }
        }
        return $this->_connections[$name];
    }
}