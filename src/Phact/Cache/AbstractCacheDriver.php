<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/02/17 12:19
 */

namespace Phact\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

abstract class AbstractCacheDriver implements CacheInterface
{
    public $serializer;

    public $timeout = 3600;

    public $prefix = '';

    /** @var bool */
    protected $enabled = true;

    public function has($key): bool
    {
        return $this->hasValue($this->buildKey($key));
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $value = $this->getValue($this->buildKey($key));
        return $value === null ? $default : $this->unserialize($value);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        $ttl = $this->prepareTtl($ttl) + time();

        return $this->setValue(
            $this->buildKey($key),
            $this->serialize($value),
            $ttl
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        return $this->deleteValue($this->buildKey($key));
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $result = true;
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl) && $result;
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys, $default = null): bool
    {
        $result = true;
        foreach ($keys as $key) {
            $result = $this->delete($key) && $result;
        }

        return $result;
    }

    public function serialize($value)
    {
        if ($this->serializer) {
            return call_user_func($this->serializer[0], $value);
        }

        return serialize($value);
    }

    public function unserialize($value)
    {
        if ($this->serializer) {
            return call_user_func($this->serializer[1], $value);
        }

        return unserialize($value);
    }

    public function buildKey($key): string
    {
        if (!is_string($key)) {
            $key = serialize($key);
        }
        return md5($key);
    }

    public function isEnabled(): bool
    {
        return (bool)$this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    protected function prepareTtl($ttl): int
    {
        if (is_object($ttl)) {
            if ($ttl instanceof DateInterval) {
                return (new \DateTime())->setTimestamp(0)->add($ttl)->getTimestamp();
            }

            throw new \RuntimeException('Support only DateInterval objects');
        }

        if ($ttl) {
            return (int)$ttl;
        }

        return $this->timeout;
    }

    /**
     * @param string $key
     * @return bool
     */
    abstract protected function hasValue(string $key): bool;

    /**
     * @param string $key
     * @return mixed
     */
    abstract protected function getValue(string $key);

    /**
     * @param string $key
     * @param mixed $data
     * @param null|int|DateInterval $ttl
     * @return bool
     */
    abstract protected function setValue(string $key, $data, int $ttl): bool;


    /**
     * @param string $key
     * @return bool
     */
    abstract protected function deleteValue(string $key): bool;
}