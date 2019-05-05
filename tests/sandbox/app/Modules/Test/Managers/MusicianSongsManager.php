<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/05/2019 16:05
 */

namespace Modules\Test\Managers;


use Phact\Orm\HasManyManager;

class MusicianSongsManager extends HasManyManager
{
    public function byNameSelection()
    {
        return $this->order(['name']);
    }
}