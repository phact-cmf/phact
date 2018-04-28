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

trait LoggerHandle
{
    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logEmergency($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('emergency', $message, $context, $logger);
    }

    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logAlert($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('alert', $message, $context, $logger);
    }

    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logCritical($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('critical', $message, $context, $logger);
    }

    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logError($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('error', $message, $context, $logger);
    }

    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logWarning($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('warning', $message, $context, $logger);
    }

    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logNotice($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('notice', $message, $context, $logger);
    }

    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logInfo($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('info', $message, $context, $logger);
    }

    /**
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logDebug($message, array $context = [], $logger = 'default')
    {
        $this->logHandle('debug', $message, $context, $logger);
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @param string $logger
     */
    public function logHandle($level, $message, array $context = [], $logger = 'default')
    {
        if ($logger = $this->getLogger($logger)) {
            $logger->{$level}($message, $context);
        }
    }

    /**
     * @param string $name
     * @return LoggerInterface
     */
    abstract public function getLogger($name = 'default');
}