<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 01/08/16 15:06
 */

namespace Phact\Request;

use ArrayAccess;
use Countable;

class CookieCollection implements ArrayAccess, Countable
{
    public function add($key, $value, $expire = 0, $path = '/', $domain = false, $secure = false, $httponly = false)
    {
        setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    public function has($key)
    {
        return array_key_exists($key, $_COOKIE);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $_COOKIE[$key] : $default;
    }

    public function all()
    {
        return $_COOKIE;
    }


    public function remove($key)
    {
        if ($this->has($key)) {
            unset($_COOKIE[$key]);
            setcookie($key, "", time()-3600, '/');
        }
    }

    public function clear()
    {
        foreach (array_keys($_COOKIE) as $key) {
            unset($_COOKIE[$key]);
            setcookie($key, "", time()-3600, '/');
        }
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $_COOKIE);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        count($_COOKIE);
    }
}