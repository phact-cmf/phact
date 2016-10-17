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
 * @date 04/10/16 14:50
 */

namespace Phact\Components;

use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Model;

class Flash
{
    use SmartProperties;

    const SESSION_KEY = 'FLASH';

    public function success($message)
    {
        $this->add($message, 'success');
    }

    public function error($message)
    {
        $this->add($message, 'error');
    }

    public function info($message)
    {
        $this->add($message, 'info');
    }
    
    /**
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

    public function getMessages()
    {
        return Phact::app()->request->session->get(self::SESSION_KEY, []);
    }

    public function setMessages($messages = [])
    {
        Phact::app()->request->session->add(self::SESSION_KEY, $messages);
    }

    public function clearMessages()
    {
        Phact::app()->request->session->remove(self::SESSION_KEY);
    }

    public function read()
    {
        $messages = $this->getMessages();
        $this->clearMessages();
        return $messages;
    }
}