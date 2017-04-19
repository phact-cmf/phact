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
 * @date 14/04/16 07:54
 */

namespace Phact\Orm;

use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;
use Phact\Orm\Having\Having;

/**
 * Class Manager
 *
 * @property $querySet \Phact\Orm\QuerySet
 * @property $model \Phact\Orm\Model
 *
 * @package Phact\Orm
 */
class Manager
{
    use SmartProperties;

    protected $_model;

    public $querySetClass = QuerySet::class;

    public function __construct(Model $model)
    {
        $this->_model = $model;
    }

    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @return \Phact\Orm\QuerySet
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getQuerySet()
    {
        return Configurator::create($this->querySetClass, [
            'model' => $this->getModel()
        ]);
    }

    public function all()
    {
        return $this->getQuerySet()->all();
    }

    public function count()
    {
        return $this->getQuerySet()->count();
    }

    public function sum($attribute)
    {
        return $this->getQuerySet()->sum($attribute);
    }

    public function max($attribute)
    {
        return $this->getQuerySet()->max($attribute);
    }

    public function avg($attribute)
    {
        return $this->getQuerySet()->avg($attribute);
    }

    public function min($attribute)
    {
        return $this->getQuerySet()->min($attribute);
    }

    public function get()
    {
        return $this->getQuerySet()->get();
    }

    /**
     * @param array $filter
     * @return QuerySet
     */
    public function filter($filter = [])
    {
        return $this->getQuerySet()->filter($filter);
    }

    /**
     * @param array $exclude
     * @return QuerySet
     */
    public function exclude($exclude = [])
    {
        return $this->getQuerySet()->exclude($exclude);
    }

    /**
     * @param array $order
     * @return QuerySet
     */
    public function order($order = [])
    {
        return $this->getQuerySet()->order($order);
    }

    /**
     * @param Expression|Having $expression
     * @return QuerySet
     */
    public function having($expression)
    {
        return $this->getQuerySet()->having($expression);
    }

    /**
     * @param array $group
     * @return QuerySet
     */
    public function group($group = [])
    {
        return $this->getQuerySet()->group($group);
    }

    /**
     * @param $limit
     * @return QuerySet
     */
    public function limit($limit)
    {
        return $this->getQuerySet()->limit($limit);
    }

    /**
     * @param $offset
     * @return QuerySet
     */
    public function offset($offset)
    {
        return $this->getQuerySet()->offset($offset);
    }

    /**
     * @param string[] $with
     * @return QuerySet
     */
    public function with($with = [])
    {
        return $this->getQuerySet()->with($with);
    }

    /**
     * @param array $columns
     * @param bool $flat
     * @param bool $distinct
     * @return array|null|\stdClass
     */
    public function values($columns = [], $flat = false, $distinct = true)
    {
        return $this->getQuerySet()->values($columns, $flat, $distinct);
    }

    /**
     * @param $key
     * @param $value
     * @return array|null|\stdClass
     */
    public function choices($key, $value)
    {
        return $this->getQuerySet()->choices($key, $value);
    }
}