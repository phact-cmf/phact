<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/08/16 15:41
 */

namespace Phact\Template;


use Phact\Main\Phact;

trait Renderer
{
    public static function renderTemplate($template, $params = [])
    {
        return Phact::app()->template->render($template, $params);
    }
}