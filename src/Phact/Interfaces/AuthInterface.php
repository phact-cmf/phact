<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 07/08/16 15:33
 */

namespace Phact\Interfaces;

use Phact\Orm\Model;

interface AuthInterface
{
    /**
     * Authorise user
     * @param UserInterface $user
     * @return mixed
     */
    public function login(UserInterface $user);

    /**
     * De-authorise user
     * @param bool $clearSession
     * @return mixed
     */
    public function logout($clearSession = true);

    /**
     * Get current user
     * @return UserInterface
     */
    public function getUser();

    /**
     * Set current user
     * @param UserInterface $user
     * @return mixed
     */
    public function setUser(UserInterface $user);

    /**
     * Find user by uniquie login (email/phone)
     * @return UserInterface|null
     */
    public function findUserByLogin($login);

    /**
     * Find user by uniquie id
     * @return UserInterface|null
     */
    public function findUserById($id);

    /**
     * @param UserInterface $user
     * @param string $password
     * @return bool
     */
    public function verifyPassword(UserInterface $user, string $password);

    /**
     * @param UserInterface $user
     * @param string $password
     */
    public function setPassword(UserInterface $user, string $password);

    /**
     * @param UserInterface $user
     * @param string $login
     */
    public function setLogin(UserInterface $user, string $login);
}