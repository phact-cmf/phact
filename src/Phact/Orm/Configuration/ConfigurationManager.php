<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 27/01/2019 10:53
 */

namespace Phact\Orm\Configuration;

use Phact\Di\ContainerInterface;
use Phact\Event\EventManagerInterface;
use Phact\Orm\ConnectionManagerInterface;
use Psr\SimpleCache\CacheInterface;

class ConfigurationManager implements ConfigurationManagerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $_container;

    /**
     * @var ConnectionManagerInterface
     */
    protected $_connectionManager;

    /**
     * @var CacheInterface|null
     */
    protected $_cache;

    /**
     * @var EventManagerInterface|null
     */
    protected $_eventManager;

    /**
     * @var int
     */
    protected $_cacheFieldsTimeout;

    /**
     * @var int
     */
    protected $_cacheQueryTimeout;


    public function __construct(
        ContainerInterface $container,
        ConnectionManagerInterface $connectionManager,
        CacheInterface $cache = null,
        EventManagerInterface $eventManager = null)
    {
        $this->_container = $container;
        $this->_connectionManager = $connectionManager;
        $this->_cache = $cache;
        $this->_eventManager = $eventManager;
    }

    /**
     * @return int
     * @deprecated
     */
    public function setCacheFieldsTimeout(?int $timeout): self
    {
        $this->_cacheFieldsTimeout = $timeout;
        return $this;
    }

    /**
     * @return int
     * @deprecated
     */
    public function getCacheFieldsTimeout(): ?int
    {
        return $this->_cacheFieldsTimeout;
    }

    /**
     * @param int $cacheQueryTimeout
     * @return self
     */
    public function setCacheQueryTimeout(?int $cacheQueryTimeout): self
    {
        $this->_cacheQueryTimeout = $cacheQueryTimeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getCacheQueryTimeout(): ?int
    {
        return $this->_cacheQueryTimeout;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->_container;
    }

    /**
     * @return ConnectionManagerInterface
     */
    public function getConnectionManager(): ConnectionManagerInterface
    {
        return $this->_connectionManager;
    }

    /**
     * @return CacheInterface|null
     */
    public function getCache(): ?CacheInterface
    {
        return $this->_cache;
    }

    /**
     * @return null|EventManagerInterface
     */
    public function getEventManager(): ?EventManagerInterface
    {
        return $this->_eventManager;
    }
}