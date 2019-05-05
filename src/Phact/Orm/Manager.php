<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 14/04/16 07:54
 */

namespace Phact\Orm;

use Phact\Exceptions\InvalidAttributeException;
use Phact\Exceptions\InvalidConfigException;
use Phact\Exceptions\UnknownMethodException;
use Phact\Helpers\Configurator;
use Phact\Helpers\SmartProperties;
use Phact\Orm\Having\Having;
use Phact\Pagination\PaginableInterface;

/**
 * Class Manager
 *
 * @property $querySet \Phact\Orm\QuerySet
 * @property $model \Phact\Orm\Model
 *
 * @package Phact\Orm
 */
class Manager implements PaginableInterface, QuerySetInterface
{
    use SmartProperties;

    protected $_model;

    protected $_querySet;

    protected $_activeSelection;

    protected $_cleanSelection = false;

    public $querySetClass = QuerySet::class;

    public function __construct(Model $model, QuerySet $querySet = null)
    {
        $this->_model = $model;
        if ($querySet !== null) {
            if ($querySet->getModelClass() === get_class($model)) {
                $this->_querySet = $querySet;
            } else {
                throw new InvalidAttributeException('QuerySet model class must match the model class');
            }
        }
    }

    public function getModel()
    {
        return $this->_model;
    }

    protected function nextManager(QuerySet $querySet): self
    {
        $class = static::class;
        return new $class($this->_model, $querySet);
    }

    /**
     * @return \Phact\Orm\QuerySet
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    protected function createQuerySet()
    {
        return Configurator::create($this->querySetClass, [
            'model' => $this->getModel()
        ]);
    }

    /**
     * @return \Phact\Orm\QuerySet
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    public function getQuerySet()
    {
        return $this->_querySet ?: $this->createQuerySet();
    }

    public function all()
    {
        return $this->getQuerySet()->all();
    }

    public function get()
    {
        return $this->getQuerySet()->get();
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

    /**
     * @param array $filter
     * @return self
     */
    public function filter($filter = [])
    {
        return $this->nextManager($this->getQuerySet()->filter($filter));
    }

    /**
     * @param array $exclude
     * @return self
     */
    public function exclude($exclude = [])
    {
        return $this->nextManager($this->getQuerySet()->exclude($exclude));
    }

    /**
     * @param array $order
     * @return self
     */
    public function order($order = [])
    {
        return $this->nextManager($this->getQuerySet()->order($order));
    }

    /**
     * @param Expression|Having $expression
     * @return self
     */
    public function having($expression)
    {
        return $this->nextManager($this->getQuerySet()->having($expression));
    }

    /**
     * @param array $group
     * @return self
     */
    public function group($group = [])
    {
        return $this->nextManager($this->getQuerySet()->group($group));
    }

    /**
     * @param $limit
     * @return self
     */
    public function limit($limit)
    {
        return $this->nextManager($this->getQuerySet()->limit($limit));
    }

    /**
     * @param $offset
     * @return self
     */
    public function offset($offset)
    {
        return $this->nextManager($this->getQuerySet()->offset($offset));
    }

    /**
     * @param string[] $with
     * @return self
     */
    public function with($with = [])
    {
        return $this->nextManager($this->getQuerySet()->with($with));
    }

    /**
     * @param array $select
     * @return self
     */
    public function select($select = []): self
    {
        return $this->nextManager($this->getQuerySet()->select($select));
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

    /**
     * @param $query
     * @param array $params
     * @return array
     */
    public function raw($query, $params = [])
    {
        return $this->getQuerySet()->raw($query, $params);
    }

    /**
     * @param $query
     * @param array $params
     * @return Model[]
     */
    public function rawAll($query, $params = [])
    {
        return $this->getQuerySet()->rawAll($query, $params);
    }

    /**
     * @param $query
     * @param array $params
     * @return Model|null
     */
    public function rawGet($query, $params = [])
    {
        return $this->getQuerySet()->rawGet($query, $params);
    }

    public function update($data = [])
    {
        return $this->getQuerySet()->update($data);
    }

    public function delete()
    {
        return $this->getQuerySet()->delete();
    }

    public function allSql()
    {
        return $this->getQuerySet()->allSql();
    }

    public function getSql()
    {
        return $this->getQuerySet()->getSql();
    }

    public function valuesSql($columns = [], $flat = false, $distinct = true)
    {
        return $this->getQuerySet()->valuesSql($columns, $flat, $distinct);
    }

    public function updateSql($data = [])
    {
        return $this->getQuerySet()->updateSql($data);
    }

    public function deleteSql()
    {
        return $this->getQuerySet()->deleteSql();
    }

    public function countSql()
    {
        return $this->getQuerySet()->countSql();
    }

    public function maxSql($attribute)
    {
        return $this->getQuerySet()->maxSql($attribute);
    }

    public function minSql($attribute)
    {
        return $this->getQuerySet()->minSql($attribute);
    }

    public function avgSql($attribute)
    {
        return $this->getQuerySet()->avgSql($attribute);
    }

    public function sumSql($attribute)
    {
        return $this->getQuerySet()->sumSql($attribute);
    }

    public function hasNamedSelection(string $name): bool
    {
        return method_exists($this, $name . 'Selection');
    }

    /**
     * @param string $name
     * @return Manager
     * @throws UnknownMethodException
     */
    public function processNamedSelection(string $name): Manager
    {
        if ($this->hasNamedSelection($name)) {
            return $this->{$name}();
        }
        $class = static::class;
        throw new UnknownMethodException("Invalid named selection method {$class}::{$name}");
    }

    public function setPaginationLimit($limit): PaginableInterface
    {
        return $this->limit($limit);
    }

    public function setPaginationOffset($offset): PaginableInterface
    {
        return $this->offset($offset);
    }

    public function getPaginationTotal()
    {
        return $this->count();
    }

    public function getPaginationData($dataType = null)
    {
        if ($dataType === 'raw') {
            return $this->values();
        }
        return $this->all();
    }

    public function __call($name, $arguments)
    {
        $selectionName = $name . 'Selection';
        if (method_exists($this, $selectionName)) {
            $manager = call_user_func_array([$this, $selectionName], $arguments);
            return $manager->setActiveSelection($name)->setCleanSelection($this->_querySet === null);
        }
        throw new UnknownMethodException("Call unknown method {$name}");
    }

    /**
     * @param mixed $activeSelection
     * @return Manager
     */
    public function setActiveSelection($activeSelection): Manager
    {
        $this->_activeSelection = $activeSelection;
        return $this;
    }

    /**
     * @param bool $cleanSelection
     * @return Manager
     */
    public function setCleanSelection(bool $cleanSelection): Manager
    {
        $this->_cleanSelection = $cleanSelection;
        return $this;
    }
}