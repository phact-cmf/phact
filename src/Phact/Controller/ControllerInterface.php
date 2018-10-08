<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 23/09/2018 13:42
 */

namespace Phact\Controller;

/**
 * MVC Controller
 *
 * Interface ControllerInterface
 * @package Phact\Controller
 */
interface ControllerInterface
{
    /**
     * Method, calls before action
     *
     * @param $action
     * @param $params
     * @return mixed
     */
    public function beforeActionInternal($action, $params);

    /**
     * Method, calls after action
     *
     * @param $action
     * @param $params
     * @return mixed
     */
    public function afterActionInternal($action, $params, $response);
}