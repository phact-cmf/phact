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


use Psr\SimpleCache\CacheInterface;

abstract class CacheDriver implements CacheInterface
{
    public $serializer;

    public $timeout = 3600;

    public $prefix = '';

    public function get($key, $default = null)
    {
        $value = $this->getValue($this->buildKey($key));
        return is_null($value) ? $default : $this->unserialize($value);
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        $result = true;
        foreach ($keys as $key => $value) {
            $result = $this->set($key, $value, $ttl) && $result;
        }
        return $result;
    }

    public function deleteMultiple($keys, $default = null)
    {
        $result = true;
        foreach ($keys as $key) {
            $result = $this->delete($key) && $result;
        }
        return $result;
    }

    public function set($key, $value, $ttl = null)
    {
        $ttl = $ttl ?: $this->timeout;
        return $this->setValue($this->buildKey($key), $this->serialize($value), $ttl);
    }

    public function serialize($value)
    {
        if ($this->serializer) {
            return call_user_func($this->serializer[0], $value);
        } else {
            return serialize($value);
        }
    }

    public function unserialize($value)
    {
        if ($this->serializer) {
            return call_user_func($this->serializer[1], $value);
        } else {
            return unserialize($value);
        }
    }

    public function buildKey($key)
    {
        if (!is_string($key)) {
            $key = serialize($key);
        }
        return $this->prefix . md5($key);
    }

    abstract protected function getValue($key);

    /**
     * @param $key
     * @param $data
     * @param $timeout
     * @return bool
     */
    abstract protected function setValue($key, $data, $timeout);
}