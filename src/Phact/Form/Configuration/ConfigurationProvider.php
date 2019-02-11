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

use Phact\Exceptions\InvalidConfigException;

/**
 * Proxy class singleton for configuration managing
 *
 * Class ConfigurationProvider
 * @package Phact\Orm\Configuration
 */
class ConfigurationProvider
{
    /**
     * @var ConfigurationManagerInterface
     */
    private $manager;

    /**
     * @var self
     */
    private static $instance;

    /**
     * @return ConfigurationProvider
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setManager(ConfigurationManagerInterface $manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return ConfigurationManagerInterface
     * @throws InvalidConfigException
     */
    public function getManager(): ConfigurationManagerInterface
    {
        if (!$this->manager) {
            throw new InvalidConfigException('Please, provide correct ConfigurationManager at first');
        }
        return $this->manager;
    }

    private function __construct ()
    {
    }

    private function __clone ()
    {
    }
}