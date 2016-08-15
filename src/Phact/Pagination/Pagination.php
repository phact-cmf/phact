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
 * @date 15/08/16 15:58
 */

namespace Phact\Pagination;


use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;
use Phact\Orm\Manager;
use Phact\Orm\QuerySet;

class Pagination
{
    use SmartProperties;

    /**
     * @var array|Manager|QuerySet
     */
    protected $_provider;

    protected $_limit = 0;

    protected $_offset = 0;

    protected $_page = 1;

    protected $_id = 0;

    protected static $_key = 0;

    public $pageKeyTemplate = 'Pagination_{id}';

    public $pageSizeKeyTemplate = 'Pagination_Size_{id}';

    public function __construct($provider, $options = [])
    {
        self::$_key++;
        $this->_id = self::$_key;
        $this->_provider = $provider;
        Configurator::configure($this, $options);
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getLimit()
    {
        return $this->_limit;
    }

    public function setLimit($limit)
    {
        $this->_limit = $limit;
    }

    public function getOffset()
    {
        return $this->_offset;
    }

    public function setOffset($offset)
    {
        $this->_offset = $offset;
    }

    public function getPage()
    {
        return $this->_page;
    }

    public function setPage($page)
    {
        $this->_page = $page;
    }

    public function getRequestPage()
    {
        $key = $this->getRequestPageKey();
        return $this->getRequestValue($key);
    }

    public function getRequestPageSize()
    {
        $key = $this->getRequestPageSizeKey();
        return $this->getRequestValue($key);
    }

    public function getRequestPageKey()
    {
        return $this->buildRequestKey($this->pageKeyTemplate);
    }

    public function getRequestPageSizeKey()
    {
        return $this->buildRequestKey($this->pageSizeKeyTemplate);
    }

    public function buildRequestKey($template)
    {
        return strtr($template, [
            '{id}' => $this->getId()
        ]);
    }

    public function getRequestValue($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
}