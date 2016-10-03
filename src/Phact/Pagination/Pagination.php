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


use Phact\Exceptions\InvalidConfigException;
use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Manager;
use Phact\Orm\QuerySet;
use Phact\Template\Renderer;
use Traversable;

/**
 * Class Pagination
 *
 * @property $data array
 *
 * @package Phact\Pagination
 */
class Pagination
{
    use SmartProperties, Renderer;

    /**
     * @var array|PaginableInterface
     */
    protected $_provider;

    protected $_page;

    protected $_pageSize;

    protected $_defaultPage = 1;

    protected $_defaultPageSize = 10;

    protected $_id = 0;

    protected static $_key = 0;

    protected $_dataType;

    protected $_total = null;

    protected $_lastPage = null;

    public $redirectInvalidPage = true;

    public $pageKeyTemplate = 'Pagination_{id}';

    public $pageSizeKeyTemplate = 'Pagination_Size_{id}';

    public function __construct($provider, $options = [])
    {
        if (!($provider instanceof PaginableInterface) && !(is_array($provider))) {
            throw new InvalidConfigException("Pagination \$provider must be instance of an array or PaginableInterface");
        }
        self::$_key++;
        $this->_id = self::$_key;
        $this->_provider = $provider;
        Configurator::configure($this, $options);
        if ($this->redirectInvalidPage) {
            $this->checkInvalidPage();
        }
    }

    public function checkInvalidPage()
    {
        $page = $this->fetchPage();
        $lastPage = $this->getLastPage();
        if ($page > $lastPage) {
            Phact::app()->request->redirect($this->getUrl($lastPage));
        } elseif ($page < 1) {
            Phact::app()->request->redirect($this->getUrl(1));
        }
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = $id;
    }

    public function getPage()
    {
        return $this->_page;
    }

    public function setPage($page)
    {
        $this->_page = $page;
    }

    public function getPageSize()
    {
        return $this->_pageSize;
    }

    public function setPageSize($pageSize)
    {
        $this->_pageSize = $pageSize;
    }

    public function getDefaultPage()
    {
        return $this->_defaultPage;
    }

    public function setDefaultPage($page)
    {
        $this->_defaultPage = $page;
    }

    public function getDefaultPageSize()
    {
        return $this->_defaultPageSize;
    }

    public function setDefaultPageSize($pageSize)
    {
        $this->_defaultPageSize = $pageSize;
    }

    public function getDataType()
    {
        return $this->_dataType;
    }

    public function setDataType($page)
    {
        $this->_dataType = $page;
    }

    public function fetchPage()
    {
        if (!$this->getPage()) {
            $page = $this->getRequestPage();
            if (!$page) {
                $page = $this->getDefaultPage();
            }
            if ($page <= 0) {
                $page = 1;
            } elseif ($page > $this->getLastPage()) {
                $page = $this->getLastPage();
            }
            $this->setPage($page);
        }
        return $this->getPage();
    }

    public function fetchPageSize()
    {
        if (!$this->getPageSize()) {
            $pageSize = $this->getRequestPageSize();
            if (!$pageSize) {
                $pageSize = $this->getDefaultPageSize();
            }
            $this->setPageSize($pageSize);
        }
        return $this->getPageSize();
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

    public function getFirstPage()
    {
        return 1;
    }

    public function getFirstPageUrl()
    {
        return $this->getUrl($this->getFirstPage());
    }

    public function getLastPage()
    {
        if (is_null($this->_lastPage)) {
            $pageSize = $this->fetchPageSize();
            $total = $this->getTotal();
            $result = ceil($total / $pageSize);
            $this->_lastPage = $result >= 1 ? $result : 1;
        }
        return $this->_lastPage;
    }

    public function getLastPageUrl()
    {
        return $this->getUrl($this->getLastPage());
    }

    public function getPreviousPage()
    {
        $page = $this->fetchPage() - 1;
        if ($this->hasPage($page)) {
            return $page;
        }
        return null;
    }

    public function hasPreviousPage()
    {
        return (bool) $this->getPreviousPage();
    }

    public function getPreviousPageUrl()
    {
        return $this->hasPreviousPage() ? $this->getUrl($this->getPreviousPage()) : null;
    }

    public function getNextPage()
    {
        $page = $this->fetchPage() + 1;
        if ($this->hasPage($page)) {
            return $page;
        }
        return null;
    }

    public function hasNextPage()
    {
        return (bool) $this->getNextPage();
    }

    public function getNextPageUrl()
    {
        return $this->hasNextPage() ? $this->getUrl($this->getNextPage()) : null;
    }

    public function hasPage($page)
    {
        $lastPage = $this->getLastPage();
        if ($page >= 1 && $page <= $lastPage) {
            return true;
        }
        return false;
    }

    public function getUrl($page)
    {
        $query = Phact::app()->request->getQueryArray();
        $query[$this->getRequestPageKey()] = $page;
        return Phact::app()->request->getPath() . '?' . http_build_query($query);
    }

    public function getTotal()
    {
        if (is_null($this->_total)) {
            if (is_array($this->_provider)) {
                $this->_total = count($this->_provider);
            } elseif ($this->_provider instanceof PaginableInterface) {
                $this->_total = $this->_provider->getPaginationTotal();
            } else {
                $this->_total = null;
            }
        }
        return $this->_total;
    }

    public function getData()
    {
        $page = $this->fetchPage();
        $pageSize = $this->fetchPageSize();

        $limit = $pageSize;
        $offset = ($page - 1) * $pageSize;

        if (is_array($this->_provider)) {
            return array_slice($this->_provider, $offset, $limit);
        } elseif ($this->_provider instanceof PaginableInterface) {
            $this->_provider->setPaginationLimit($limit);
            $this->_provider->setPaginationOffset($offset);
            return $this->_provider->getPaginationData($this->getDataType());
        } else {
            return [];
        }
    }

    public function render($template = 'pagination/default.tpl')
    {
        return $this->renderTemplate($template, [
            'pagination' => $this
        ]);
    }
}