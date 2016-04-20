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
 * @date 15/04/16 15:52
 */

namespace Phact\Orm;


class Lookup
{
    public static $defaultLookup = 'exact';
    
    public static function map()
    {
        return [
            'exact',
            'contains',
            'in',
            'gt',
            'gte',
            'lt',
            'lte',
            'startswith',
            'endswith',
            'range',
            'isnull',
            'regex'
        ];
    }
}