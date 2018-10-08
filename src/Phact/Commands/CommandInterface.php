<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 13:47
 */

namespace Phact\Commands;


interface CommandInterface
{
    /**
     * Description for help
     */
    public function getDescription();

    /**
     * Get command name
     * @return string
     */
    public function getName();

    /**
     * Verbose command info
     * @return string
     */
    public function getVerbose();
}