<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/05/2019 16:26
 */

namespace Phact\Orm;


interface FieldManagedInterface
{
    public function getManager(): Manager;
}