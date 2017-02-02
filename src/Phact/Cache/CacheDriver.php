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
 * @date 02/02/17 12:19
 */

namespace Phact\Cache;


abstract class CacheDriver
{
    public $serializer;

    public $timeout = 3600;

    public $prefix = '';


    public function get($key, $default = null)
    {
        $value = $this->getValue($this->buildKey($key));
        return is_null($value) ? $default : $this->unserialize($value);
    }

    public function set($key, $value, $timeout = null)
    {
        $timeout = $timeout ?: $this->timeout;
        return $this->setValue($this->buildKey($key), $this->serialize($value), $timeout);
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

    abstract protected function setValue($key, $data, $timeout);
}