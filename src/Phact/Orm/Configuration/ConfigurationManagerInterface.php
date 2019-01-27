<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 27/01/2019 10:54
 */

namespace Phact\Orm\Configuration;

use Phact\Di\ContainerInterface;
use Phact\Event\EventManagerInterface;
use Phact\Orm\ConnectionManagerInterface;
use Psr\SimpleCache\CacheInterface;

interface ConfigurationManagerInterface
{
    public function getCacheFieldsTimeout(): ?int;
    public function getCacheQueryTimeout(): ?int;
    public function getContainer(): ContainerInterface;
    public function getConnectionManager(): ConnectionManagerInterface;
    public function getCache(): ?CacheInterface;
    public function getEventManager(): ?EventManagerInterface;
}