<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 13:35
 */

namespace Phact\Components;

/**
 * A flash message is used in order to keep a message in session through one or several requests of the same user
 *
 * Interface FlashInterface
 * @package Phact\Components
 */
interface FlashInterface
{
    /**
     * Add message with type success
     * @param $message
     * @return mixed
     */
    public function success($message);

    /**
     * Add message with type error
     * @param $message
     * @return mixed
     */
    public function error($message);

    /**
     * Add message with type info
     * @param $message
     * @return mixed
     */
    public function info($message);

    /**
     * Add message with given type
     * @param $message
     * @param string $type "success"|"error"|"info"
     */
    public function add($message, $type = 'success');

    /**
     * Reads messages from the session and delete them from session
     * @return mixed
     */
    public function read();

    /**
     * Reads messages from the session, but doesn't delete them
     * @return mixed
     */
    public function getMessages();

    /**
     * Clear all messages
     * @return mixed
     */
    public function clearMessages();
}