<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 27/01/2019 11:14
 */

namespace Phact\Orm;


use Doctrine\DBAL\Connection;

interface ConnectionManagerInterface
{
    /**
     * Get default connection name
     *
     * @return string
     */
    public function getDefaultConnection(): string;

    /**
     * Set connections configuration
     *
     * @param array $config
     * @return mixed
     */
    public function setConnections($config = []);

    /**
     * Get connection by name
     *
     * @param null $name
     * @return Connection
     */
    public function getConnection($name = null): Connection;
}