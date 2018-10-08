<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/10/16 14:50
 */

namespace Phact\Components;

use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Request\Session;

/**
 * Flash messages (short-life messages)
 *
 * A flash message is used in order to keep a message in session through one or several requests of the same user
 *
 * Class Flash
 * @package Phact\Components
 */
class Flash implements FlashInterface
{
    use SmartProperties;

    const SESSION_KEY = 'FLASH';

    /**
     * @var Session
     */
    protected $_session;

    public function __construct(Session $session)
    {
        $this->_session = $session;
    }

    /**
     * Add message with type success
     * @param $message
     * @return mixed
     */
    public function success($message)
    {
        $this->add($message, 'success');
    }

    /**
     * Add message with type error
     * @param $message
     * @return mixed
     */
    public function error($message)
    {
        $this->add($message, 'error');
    }

    /**
     * Add message with type info
     * @param $message
     * @return mixed
     */
    public function info($message)
    {
        $this->add($message, 'info');
    }

    /**
     * Add message with given type
     * @param $message
     * @param string $type "success"|"error"|"info"
     */
    public function add($message, $type = 'success')
    {
        $messages = $this->getMessages();
        $messages[] = [
            'message' => $message,
            'type' => $type
        ];
        $this->setMessages($messages);
    }

    /**
     * Reads messages from the session, but doesn't delete them
     * @return mixed
     */
    public function getMessages()
    {
        return array_merge($this->_session->get(self::SESSION_KEY, []), []);
    }

    /**
     * Add messages to session
     * @param array $messages
     */
    protected function setMessages($messages = [])
    {
        $this->_session->add(self::SESSION_KEY, $messages);
    }

    /**
     * Clear all messages
     * @return mixed
     */
    public function clearMessages()
    {
        $this->_session->remove(self::SESSION_KEY);
    }

    /**
     * Reads messages from the session and delete them from session
     * @return mixed
     */
    public function read()
    {
        $messages = $this->getMessages();
        $this->clearMessages();
        return $messages;
    }
}