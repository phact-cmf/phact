<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 19/07/2018 16:19
 */

namespace Phact\Translate;

use Phact\Di\ComponentFetcher;
use Phact\Main\Phact;

trait Translator
{
    use ComponentFetcher;

    /**
     * @param string $domain If $key is not set, uses as $key, $domain is empty
     * @param string $key
     * @param int|null $number
     * @param null|array $parameters
     * @param null|string $locale
     * @return string
     */
    public static function t($domain, $key = "", $number = null, $parameters = [], $locale = null)
    {
        $translator = self::fetchComponent(Translate::class);
        if ($translator) {
            if (!$key) {
                $key = $domain;
                $domain = "";
            }
            if (!$domain && method_exists(static::class, 'getModuleName')) {
                $domain = static::getModuleName();
            }
            return $translator->t($domain, $key, $number, $parameters, $locale);
        }
        return $key;
    }
}