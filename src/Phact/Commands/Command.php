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
 * @date 15/09/16 11:37
 */

namespace Phact\Commands;

abstract class Command
{
    abstract public function handle($arguments = []);
}