<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company OrderTarget
 * @site http://ordertarget.ru
 * @date 26/04/18 20:54
 */

namespace Phact\Log;


use Phact\Main\Phact;
use Psr\Log\LoggerInterface;

trait Logger
{
    /**
     * @var LoggerInterface|null
     */
    protected $_logger;

    use LoggerHandle;

    /**
     * @param $name
     * @return null|LoggerInterface $logger
     */
    public function getLogger($name = 'default')
    {
        return $this->_logger ?: null;
    }
}