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

interface ConfigurationManagerInterface
{
    public function getContainer(): ContainerInterface;
}