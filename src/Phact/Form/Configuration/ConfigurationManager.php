<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 09/02/2019 08:25
 */

namespace Phact\Form\Configuration;

use Phact\Di\ContainerInterface;
use Phact\Event\EventManagerInterface;

class ConfigurationManager implements ConfigurationManagerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $_container;

    /**
     * @var EventManagerInterface|null
     */
    protected $_eventManager;

    public function __construct(
        ContainerInterface $container,
        EventManagerInterface $eventManager = null)
    {
        $this->_container = $container;
        $this->_eventManager = $eventManager;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->_container;
    }

    /**
     * @return null|EventManagerInterface
     */
    public function getEventManager(): ?EventManagerInterface
    {
        return $this->_eventManager;
    }
}