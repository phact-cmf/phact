<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 22/09/2018 17:40
 */

namespace Phact\Components;


interface BreadcrumbsInterface
{
    public function setActive($name);

    public function getActive();

    public function to($name);

    public function clear();

    public function add($name, $url = null, $params = []);

    public function get($name);
}