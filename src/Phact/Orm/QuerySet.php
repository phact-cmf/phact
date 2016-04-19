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
 * @date 14/04/16 07:53
 */

namespace Phact\Orm;


use InvalidArgumentException;
use Phact\Helpers\SmartProperties;

/**
 * Class QuerySet
 *
 * @property $queryLayer QueryLayer
 *
 * @package Phact\Orm
 */
class QuerySet
{
    use SmartProperties;

    /**
     * @var Model
     */
    public $model;

    protected $_queryLayer;

    protected $_filter = [];
    protected $_exclude = [];
    protected $_order = [];

    protected $_where = [];

    public static function nextQuerySet($qs)
    {
        return $qs;
    }

    public function getQueryLayer()
    {
        if (is_null($this->_queryLayer)) {
            $this->_queryLayer = new QueryLayer();
        }
        $this->_queryLayer->querySet = $this;
        return $this->_queryLayer;
    }

    public function createModel($row)
    {
        $class = $this->model->className();
        /* @var $model Model */
        $model = new $class;
        $model->setDbData($row);
        return $model;
    }

    public function createModels($data)
    {
        $result = [];
        foreach ($data as $row) {
            $result[] = $this->createModel($row);
        }
        return $result;
    }

    public function all()
    {
        $data = $this->getQueryLayer()->all();
        return $this->createModels($data);
    }

    public function get()
    {
        $row = $this->getQueryLayer()->get();
        return $row ? $this->createModel($row) : null;
    }

    public function filter($filter = [])
    {
        if (!is_array($filter)) {
            throw new InvalidArgumentException('QuerySet::filter() accept only arrays');
        }
        if (!empty($filter)) {
            $this->_filter[] = $filter;
        }
        return $this->nextQuerySet($this);
    }

    public function exclude($exclude = [])
    {
        if (!is_array($exclude)) {
            throw new InvalidArgumentException('QuerySet::exclude() accept only arrays');
        }
        if (!empty($exclude)) {
            $this->_exclude[] = $exclude;
        }
        return $this->nextQuerySet($this);
    }

    public function order($order = [])
    {
        if (is_string($order)) {
            $order = [$order];
        } elseif (!is_array($order)) {
            throw new InvalidArgumentException('QuerySet::order() accept only arrays or strings');
        }

        $this->_order[] = $order;
        return $this->nextQuerySet($this);
    }

    public function buildCondition($key, $value)
    {
        explode('__', $key);
    }

    public function buildConditions($data)
    {
        $conditions = [];
        foreach ($data as $key => $condition) {
            if (is_numeric($key)) {
                if (is_array($condition)) {
                    $conditions[] = $this->buildConditions($condition);
                } else {
                    throw new InvalidArgumentException("Condition is invalid. Please, check condition structure for methods QuerySet::filter() and QuerySet::exclude().");
                }
            } else {
                $condition = $this->buildCondition($key, $condition);
            }
        }
        return $conditions;
    }

    public function build()
    {
        $filter = null;
        if ($this->_filter) {
            $filter = $this->buildConditions($this->_filter);
        }
        $exclude = null;
        if ($this->_exclude) {
            $exclude = $this->buildConditions(Q::notQ($this->_exclude));
        }
        if (!$filter || !$exclude) {
            $this->_where = $filter ? $filter : $exclude;
        } else {
            $this->_where = Q::andQ([$filter,$exclude]);
        }
    }
}