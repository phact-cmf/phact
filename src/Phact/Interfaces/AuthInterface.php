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
 * @date 07/08/16 15:33
 */

namespace Phact\Interfaces;


use Phact\Orm\Model;

interface AuthInterface
{
    public function login(Model $user);

    public function logout($clearSession = true);

    public function getUser();

    public function setUser(Model $user);
}