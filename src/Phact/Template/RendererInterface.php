<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 22/09/2018 16:56
 */

namespace Phact\Template;


interface RendererInterface
{
    /**
     * Render template
     *
     * @param $template
     * @param array $params
     * @return mixed
     */
    public function render($template, $params = []);
}