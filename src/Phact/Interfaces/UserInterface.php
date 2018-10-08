<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 03/10/2018 16:53
 */

namespace Phact\Interfaces;


interface UserInterface
{
    /**
     * Get uniquie id for user
     * @return bool
     */
    public function getId();

    /**
     * Get uniquie login for user
     * @return bool
     */
    public function getLogin();

    /**
     * Set uniquie login for user
     */
    public function setLogin(string $login);

    /**
     * Is user guest (not authorised)
     * @return bool
     */
    public function getIsGuest();

    /**
     * Is user superuser - has all permissions
     * @return bool
     */
    public function getIsSuperuser();

    /**
     * Set hashed password
     */
    public function setPassword(string $hashedPassword);

    /**
     * Get hashed password
     */
    public function getPassword();
}