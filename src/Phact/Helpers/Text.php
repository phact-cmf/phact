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
 * @date 14/04/16 07:17
 */

namespace Phact\Helpers;


class Text
{
    /**
     * Converts camel case string to underscore string.
     *
     * WARN! NOT MB SAFE!
     *
     * Examples:
     *
     * 'simpleTest' => 'simple_test'
     * 'easy' => 'easy'
     * 'HTML' => 'html'
     * 'simpleXML' => 'simple_xml'
     * 'PDFLoad' => 'pdf_load'
     *
     * @param $input string
     * @return string
     */
    public static function camelCaseToUnderscores($input) {
        return ltrim(strtolower(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', '_$0', $input)), '_');
    }

    public static function ucfirst($string, $enc = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }

    public static function removePrefix($prefix, $text)
    {
        if (0 === mb_strpos($text, $prefix, null, 'UTF-8')) {
            $text = (string) mb_substr($text, strlen($prefix), null, 'UTF-8');
        }
        return $text;
    }

    public static function startsWith($haystack, $needle)
    {
        $length = mb_strlen($needle, 'UTF-8');
        return (mb_substr($haystack, 0, $length, 'UTF-8') === $needle);
    }

    public static function endsWith($haystack, $needle)
    {
        $length = mb_strlen($needle, 'UTF-8');
        if ($length == 0) {
            return true;
        }
        return (mb_substr($haystack, -$length, null, 'UTF-8') === $needle);
    }
}