<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/02/17 12:09
 */

namespace Phact\Cache;


use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;
use Psr\SimpleCache\CacheInterface;

class CacheManager implements CacheInterface
{
    use SmartProperties;

    public $defaultDriver = 'default';
    public $fallbackDriver;

    protected $config = [];

    /** @var AbstractCacheDriver[]|CacheInterface[] */
    protected $drivers = [];

    public function setDrivers($config)
    {
        $this->config = $config;
    }

    public function getDriver(string $name = null): CacheInterface
    {
        $driver = null;

        if ($name) {
            return $this->getOrMakeDriver($name);
        }

        if ($driver = $this->getDefaultDriver()) {
            return $driver;
        }

        if ($driver = $this->getFallbackDriver()) {
            return $driver;
        }

        throw new \RuntimeException('No one cache drivers is enabled');
    }

    protected function getDefaultDriver(): ?CacheInterface
    {
        $driver = $this->getOrMakeDriver($this->defaultDriver);
        if ($driver->isEnabled()) {
            return $driver;
        }

        return null;
    }

    protected function getFallbackDriver(): ?CacheInterface
    {
        if ($this->fallbackDriver) {
            $driver = $this->getOrMakeDriver($this->fallbackDriver);
            if ($driver->isEnabled()) {
                return $driver;
            }
        }

        return null;
    }

    protected function getOrMakeDriver(string $name): CacheInterface
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->makeDriver($name);
    }

    protected function makeDriver(string $name): CacheInterface
    {
        if (!isset($this->config[$name])) {
            throw new \RuntimeException("Cache driver with name '{$name}' not exist");
        }

        $config = $this->config[$name];

        if (is_array($config)) {
            if (!isset($config['class'])) {
                throw new \RuntimeException("Class name not set in cache config '{$name}'");
            }

            $class = $config['class'];
        } else {
            $class = $config;
            $config = [];
        }

        $driver = Configurator::create($class, $config);

        $this->drivers[$name] = $driver;

        return $driver;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $timeout = null): bool
    {
        return $this->getDriver()->set($key, $value, $timeout);
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        return $this->getDriver()->get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return $this->getDriver()->delete($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $result = true;

        foreach ($this->config as $name => $config) {
            $result = $result && $this->getDriver($name)->clear();
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        return $this->getDriver()->getMultiple($keys, $default);
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->getDriver()->setMultiple($values, $ttl);
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        return $this->getDriver()->deleteMultiple($keys);
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        return $this->getDriver()->has($key);
    }
}
