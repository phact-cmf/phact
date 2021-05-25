<?php

namespace System\Cache\Drivers;

use Memcached;
use Phact\Cache\AbstractCacheDriver;

class MemCache extends AbstractCacheDriver
{
    public $memcachedClass = Memcached::class;

    /**
     * @var string memcached server hostname or IP address
     */
    public $host = '127.0.0.1';
    /**
     * @var int memcached server port
     */
    public $port = 11211;

    public $options = [
        // Memcached::OPT_BINARY_PROTOCOL
        18 => true,
        // Memcached::OPT_TCP_NODELAY
        1 => true,
        // Memcached::OPT_NO_BLOCK
        0 => true,
    ];

    /**
     * Add multiple servers to the server pool
     *
     * @link https://php.net/manual/en/memcached.addservers.php
     * @var array
     */
    public $servers = [];

    /** @var Memcached */
    protected $memcached;

    protected $isExtEnabled;

    public function __construct()
    {
        $this->enabled = null;
    }

    public function getMemcached(): ?Memcached
    {
        if ($this->memcached) {
            return $this->memcached;
        }

        if ($this->isExtEnabled === null) {
            $this->isExtEnabled = class_exists('Memcached');
            $this->setEnabled($this->isExtEnabled);
        }

        if ($this->isExtEnabled && !$this->memcached) {
            $this->memcached = $this->makeMemcached();

            $this->memcached->setOptions($this->options);
            if ($this->prefix) {
                $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, $this->prefix);
            }
            $this->memcached->addServer($this->host, $this->port);

            if ($this->servers) {
                $this->memcached->addServers($this->servers);
            }

            $isEnabled = $this->memcached->set(
                $this->buildKey(static::class),
                true
            );

            $this->setEnabled($isEnabled);
        }

        return $this->memcached;
    }

    protected function makeMemcached()
    {
        $class = $this->memcachedClass;

        return new $class();
    }

    public function isEnabled(): bool
    {
        if ($this->enabled === null) {
            $this->getMemcached();
        }

        return $this->enabled;
    }

    public function clear(): bool
    {
        $handler = $this->getMemcached();

        if (!$handler) {
            return false;
        }

        return $handler->flush();
    }

    /**
     * @inheritDoc
     */
    protected function getValue(string $key)
    {
        $handler = $this->getMemcached();

        if (!$handler) {
            return null;
        }

        $val = $handler->get($key);

        if ($handler->getResultCode() === $handler::RES_SUCCESS) {
            return $val;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function setValue(string $key, $data, int $ttl): bool
    {
        $handler = $this->getMemcached();

        if (!$handler) {
            return false;
        }

        return $handler->set($key, $data, $ttl);
    }

    /**
     * @inheritDoc
     */
    protected function hasValue(string $key): bool
    {
        $handler = $this->getMemcached();

        if (!$handler) {
            return false;
        }

        $handler->get($key);

        return $handler->getResultCode() !== $handler::RES_NOTFOUND;
    }

    /**
     * @inheritDoc
     */
    protected function deleteValue(string $key): bool
    {
        $handler = $this->getMemcached();

        if (!$handler) {
            return false;
        }

        return $handler->delete($key);
    }
}
