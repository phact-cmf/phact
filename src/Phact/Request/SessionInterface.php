<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 14:03
 */

namespace Phact\Request;

use ArrayAccess;
use Countable;

/**
 * Session management
 * Interface SessionInterface
 * @package Phact\Request
 */
interface SessionInterface extends ArrayAccess, Countable
{
    /**
     * Opens session
     * @return mixed
     */
    public function open();

    /**
     * Close session
     * @return mixed
     */
    public function close();

    /**
     * Checks session is active
     * @return mixed
     */
    public function getIsActive();

    /**
     * Get session identifier
     * @return mixed
     */
    public function getId();

    /**
     * Destroy session
     * @return mixed
     */
    public function destroy();

    /**
     * Regenerate session id
     * @param bool $deleteOldSession
     * @return mixed
     */
    public function regenerateID($deleteOldSession = false);

    /**
     * Gets the name of the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @return string the current session name
     */
    public function getName();

    /**
     * Sets the name for the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @param string $value the session name for the current session, must be an alphanumeric string.
     * It defaults to "PHPSESSID".
     */
    public function setName($value);

    /**
     * Add value to session
     * @param $key
     * @param $value
     * @return mixed
     */
    public function add($key, $value);

    /**
     * Check has session value by key
     * @param $key
     * @return mixed
     */
    public function has($key);

    /**
     * Get value from session by key
     * @param $key
     * @param null|mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Get all values from session
     * @return mixed
     */
    public function all();

    /**
     * Remove value from session by key
     * @param $key
     * @return mixed
     */
    public function remove($key);

    /**
     * Remove all values from session
     * @return mixed
     */
    public function clear();
}