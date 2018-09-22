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
use Phact\Orm\Model;
use Phact\Router\Router;

/**
 * Breadcrumbs chains management
 *
 * Class Breadcrumbs
 * @package Phact\Components
 */
class Breadcrumbs implements BreadcrumbsInterface
{
    use SmartProperties;

    const DEFAULT_LIST = 'DEFAULT';

    protected $_active = self::DEFAULT_LIST;

    protected $_lists = [];

    /**
     * @var Router
     */
    protected $_router;

    public function __construct(Router $router = null)
    {
        $this->_router = $router;
    }

    /**
     * Set active breadcrumbs chain
     *
     * @param string $name
     * @return Breadcrumbs
     */
    public function setActive($name = self::DEFAULT_LIST)
    {
        return $this->to($name);
    }

    /**
     * Get active breadcrumbs chain name
     *
     * @return string
     */
    public function getActive()
    {
        return $this->_active;
    }

    /**
     * Fluent setter of active breadcrumbs chain
     *
     * @param string $name
     * @return $this
     */
    public function to($name = self::DEFAULT_LIST)
    {
        $this->_active = $name;
        return $this;
    }

    /**
     * Clear active breadcrumbs chain
     *
     * @return array
     */
    public function clear()
    {
        return $this->_lists[$this->_active] = [];
    }

    /**
     * Add item to active breadcrumbs chain
     *
     * @param $name
     * @param null $url
     * @param array $params
     * @throws \Exception
     */
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
        if ($url && mb_strpos($url, '/', null, 'UTF-8') === false && mb_strpos($url, ':', null, 'UTF-8') >= 0 && $this->_router) {
            $url = $this->_router->url($url, $params);
        }
        $item = [
            'name' => $name,
            'url' => $url
        ];
        $this->_lists[$this->_active][] = $item;
    }

    /**
     * Get full breadcrumbs chain by name
     *
     * @param string $name
     * @return array|mixed
     */
    public function get($name = self::DEFAULT_LIST)
    {
        return isset($this->_lists[$name]) ? $this->_lists[$name] : [];
    }
}