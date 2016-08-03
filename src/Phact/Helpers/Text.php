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
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function ucfirst($string, $enc = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }
}