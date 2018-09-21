<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 19/09/2018 14:52
 */

namespace Phact\Cache;


interface CacheDriverInterface
{
    public function get($key, $default = null);

    public function set($key, $value, $timeout = null);
}