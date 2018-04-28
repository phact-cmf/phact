<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/10/16 14:50
 */

namespace Phact\Components;

use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Model;

class Breadcrumbs
{
    use SmartProperties;

    const DEFAULT_LIST = 'DEFAULT';

    protected $_active = self::DEFAULT_LIST;

    protected $_lists = [];

    public function setActive($name = self::DEFAULT_LIST)
    {
        return $this->to($name);
    }

    public function getActive()
    {
        return $this->_active;
    }

    public function to($name = self::DEFAULT_LIST)
    {
        $this->_active = $name;
        return $this;
    }

    public function clear()
    {
        return $this->_lists[$this->_active] = [];
    }

    public function add($name, $url = null, $params = [])
    {
        if (!isset($this->_lists[$this->_active])) {
            $this->_lists[$this->_active] = [];
        }
        if ($name instanceof Model) {
            if (!$url && method_exists($name, 'getAbsoluteUrl')) {
                $url = $name->getAbsoluteUrl();
            }
            $name = (string) $name;
        }
        if ($url && mb_strpos($url, '/', null, 'UTF-8') === false && mb_strpos($url, ':', null, 'UTF-8') >= 0) {
            $url = Phact::app()->router->url($url, $params);
        }
        $item = [
            'name' => $name,
            'url' => $url
        ];
        $this->_lists[$this->_active][] = $item;
    }

    public function get($name = self::DEFAULT_LIST)
    {
        return isset($this->_lists[$name]) ? $this->_lists[$name] : [];
    }
}