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
use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\Configurator;
use SessionHandlerInterface;

class Session implements ArrayAccess, Countable, SessionInterface
{
    public $debug = false;

    /**
     * @var null|SessionHandlerInterface
     */
    public $handler = null;

    public function __construct()
    {
        register_shutdown_function([$this, 'close']);
        $this->open();
    }

    public function open()
    {
        if ($this->getIsActive()) {
            return;
        }
        $this->registerSessionHandler();
        @session_start();
    }

    protected function registerSessionHandler()
    {
        if ($this->handler !== null) {
            if (!is_object($this->handler)) {
                $this->handler = Configurator::create($this->handler);
            }
            if (!$this->handler instanceof SessionHandlerInterface) {
                throw new InvalidConfigException('"' . get_class($this) . '::handler" must implement the SessionHandlerInterface.');
            }
            $this->debug ? session_set_save_handler($this->handler, false) : @session_set_save_handler($this->handler, false);
        }
    }

    public function close()
    {
        if ($this->getIsActive()) {
            $this->debug ? session_write_close() : @session_write_close();
        }
    }

    public function getIsActive()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function getId()
    {
        return session_id();
    }

    public function destroy()
    {
        if ($this->getIsActive()) {
            $this->debug ? session_unset() : @session_unset();
            $sessionId = session_id();
            $this->debug ? session_destroy() : @session_destroy();
            $this->debug ? session_id($sessionId) : @session_id($sessionId);
        }
    }

    public function regenerateID($deleteOldSession = false)
    {
        if ($this->getIsActive()) {
            if ($this->debug && !headers_sent()) {
                session_regenerate_id($deleteOldSession);
            } else {
                @session_regenerate_id($deleteOldSession);
            }
        }
    }

    /**
     * Gets the name of the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @return string the current session name
     */
    public function getName()
    {
        return session_name();
    }
    /**
     * Sets the name for the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @param string $value the session name for the current session, must be an alphanumeric string.
     * It defaults to "PHPSESSID".
     */
    public function setName($value)
    {
        session_name($value);
    }


    public function add($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $_SESSION);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $_SESSION[$key] : $default;
    }

    public function all()
    {
        return $_SESSION;
    }


    public function remove($key)
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear session variable
     *
     * Warning: Do NOT unset the whole $_SESSION with unset($_SESSION)
     * as this will disable the registering of session variables through the $_SESSION superglobal.
     */
    public function clear()
    {
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
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
        return array_key_exists($offset, $_SESSION);
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
        return count($_SESSION);
    }
}