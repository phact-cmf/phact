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
 * @date 12/04/16 18:51
 */

namespace Modules\Test\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\HasManyField;
use Phact\Orm\Fields\IntField;
use Phact\Orm\Model;

class NoteProperty extends Model
{
    const TYPE_CHAR = 1;
    const TYPE_INT = 2;

    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'type' => [
                'class' => IntField::class,
                'label' => 'Тип',
                'choices' => self::getValueClasses(),
                'default' => self::TYPE_CHAR
            ],
        ];
    }

    public static function getValueClasses()
    {
        return [
            self::TYPE_CHAR => 'Строка',
            self::TYPE_INT => 'Число'
        ];
    }

    public static function getValueClass($type)
    {
        $classes = self::getValueClasses();
        return isset($classes[$type]) ? $classes[$type] : null;
    }
}