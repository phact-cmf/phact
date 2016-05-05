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
use Phact\Orm\Fields\ManyToManyField;
use Phact\Orm\Fields\RelationField;

/**
 * Class QuerySet
 *
 * @property $queryLayer QueryLayer
 * @property $model Model
 *
 * @package Phact\Orm
 */
class QuerySet
{
    use SmartProperties;

    /**
     * @var Model
     */
    protected $_model;

    protected $_queryLayer;
    protected $_lookupManager;

    /**
     * Raw filter
     * @var array
     */
    protected $_filter = [];
    /**
     * Raw exclude
     * @var array
     */
    protected $_exclude = [];

    /**
     * Raw order
     * @var array
     */
    protected $_order = [];

    /**
     * Built order
     * @var array
     */
    protected $_orderBy = [];

    protected $_select;

    /**
     * Built filter and exclude
     * @var array
     */
    protected $_where = [];
    protected $_relations = [];
    protected $_hasManyRelations = false;

    /**
     * @return mixed QuerySet
     */
    protected function nextQuerySet()
    {
        return $this;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function setModel(Model $model)
    {
        $this->_model = $model;
    }

    public function getQueryLayer()
    {
        return new QueryLayer($this->nextQuerySet()->build());
    }

    /**
     * @return LookupManager
     */
    public function getLookupManager()
    {
        if (!$this->_lookupManager) {
            $this->_lookupManager = new LookupManager();
        }
        return $this->_lookupManager;
    }

    public function setLookupManager($lookup)
    {
        $this->_lookup = $lookup;
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

    public function all($sql = false)
    {
        $data = $this->getQueryLayer()->all($sql);
        if ($sql) {
            return $data;
        }
        return $this->createModels($data);
    }

    public function get($sql = false)
    {
        $row = $this->getQueryLayer()->get($sql);
        if ($sql) {
            return $sql;
        }
        return $row ? $this->createModel($row) : null;
    }

    /**
     * @param array $filter
     * @return QuerySet
     */
    public function filter($filter = [])
    {
        if (!is_array($filter)) {
            throw new InvalidArgumentException('QuerySet::filter() accept only arrays');
        }
        if (!empty($filter)) {
            $this->_filter[] = $filter;
        }
        return $this->nextQuerySet();
    }

    public function exclude($exclude = [])
    {
        if (!is_array($exclude)) {
            throw new InvalidArgumentException('QuerySet::exclude() accept only arrays');
        }
        if (!empty($exclude)) {
            $this->_exclude[] = $exclude;
        }
        return $this->nextQuerySet();
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function setOrder($order = [])
    {
        $this->_order = $order;
    }

    public function getOrderBy()
    {
        return $this->_orderBy;
    }

    public function order($order = [])
    {
        if (is_string($order)) {
            $order = [$order];
        } elseif (!is_array($order)) {
            throw new InvalidArgumentException('QuerySet::order() accept only arrays or strings');
        }
        $this->_order = array_merge($this->_order, $order);
        return $this->nextQuerySet();
    }

    public function appendRelation($name, $model, $joins = [])
    {
        $name = $this->stringRelationPath($name);
        $this->_relations[$name] = [
            'model' => $model,
            'joins' => $joins
        ];
    }

    public function searchRelation($name)
    {
        $path = $this->arrayRelationPath($name);

        $foundName = [];
        $searchName = [];
        $nextName = [];

        $model = $this->model;

        foreach ($path as $part) {
            $searchName[] = $part;
            $searchRelation = implode('__', $searchName);
            if ($this->hasRelation($searchRelation)) {
                $foundName[] = $part;
                $relation = $this->getRelation($searchRelation);
                $model = $relation['model'];
            } else {
                $nextName[] = $part;
            }
        }

        return [$model, $nextName, $foundName];
    }

    public function getHasManyRelations()
    {
        return $this->_hasManyRelations;
    }

    public function connectRelation($name)
    {
        /* @var $model Model */
        list($model, $path, $found) = $this->searchRelation($name);
        $full = $found;

        foreach ($path as $relationName) {
            $full[] = $relationName;
            /* @var $field \Phact\Orm\Fields|RelationField */
            if (($field = $model->getField($relationName)) && is_a($field, RelationField::class)) {
                if (is_a($field, ManyToManyField::class) && ($throughName = $field->getThroughName())) {
                    $throughRelationPath = $this->siblingRelationPath($full, $throughName);
                    if (!$this->hasRelation($throughRelationPath)) {
                        $this->connectRelation($throughRelationPath);
                    }
                }
                if ($field->getIsMany()) {
                    $this->_hasManyRelations = $field->getIsMany();
                }
                $this->appendRelation($full, $field->getRelationModel(), $field->getRelationJoins());
            } else {
                throw new InvalidArgumentException("Invalid relation name. Please, check relations in your conditions.");
            }
        }
    }

    public function hasRelation($name)
    {
        $name = $this->stringRelationPath($name);
        return isset($this->_relations[$name]);
    }

    public function getRelation($name)
    {
        if (!$name || $name == '__this') {
            return [
                'model' => $this->getModel()
            ];
        }
        if (!$this->hasRelation($name)) {
            $this->connectRelation($name);
        }
        return $this->_relations[$name];
    }

    public function stringRelationPath($path)
    {
        if (is_array($path)) {
            $path = implode('__', $path);
        }
        return $path;
    }

    public function arrayRelationPath($path)
    {
        if (is_string($path)) {
            $path = explode('__', $path);
        }
        return $path;
    }

    public function siblingRelationPath($path, $name)
    {
        $path = $this->arrayRelationPath($path);
        array_pop($path);
        return array_merge($path, [$name]);
    }

    public function parentRelationName($path)
    {
        $path = $this->arrayRelationPath($path);
        array_pop($path);
        return $this->stringRelationPath($path);
    }

    public function buildCondition($key, $value)
    {
        $info = explode('__', $key);
        $field = array_pop($info);

        $lookupManager = $this->getLookupManager();
        $lookup = $lookupManager::$defaultLookup;
        if (in_array($field, $lookupManager->map())) {
            $lookup = $field;
            $field = array_pop($info);
        }

        $relation = implode('__', $info);
        if ($relation) {
            $this->getRelation($relation);
        } else {
            $relation = '__this';
        }
        if ($value instanceof Expression) {
            $value = $this->handleExpression($value);
        }
        return compact('relation', 'field', 'lookup', 'value');
    }

    public function getRelationColumn($column)
    {
        $info = explode('__', $column);

        $field = array_pop($info);
        $relationName = implode('__', $info);
        if (!$relationName) {
            $relationName = '__this';
        }
        return [$relationName, $field];
    }

    public function handleRelationColumn($column)
    {
        list($relationName, $field) = $this->getRelationColumn($column);
        $this->getRelation($relationName);
        return [$relationName, $field];
    }

    public function buildConditions($data)
    {
        $conditions = [];
        foreach ($data as $key => $condition) {
            if ($key == 0 && in_array($condition, ['not', 'and', 'or'])) {
                $conditions[] = $condition;
            } elseif (is_numeric($key)) {
                if (is_array($condition)) {
                    $conditions[] = $this->buildConditions($condition);
                } elseif ($condition instanceof Expression) {
                    $conditions[] = $this->handleExpression($condition);
                } else {
                    throw new InvalidArgumentException("Condition is invalid. Please, check condition structure for methods QuerySet::filter() and QuerySet::exclude().");
                }
            } else {
                $conditions[] = $this->buildCondition($key, $condition);
            }
        }
        return $conditions;
    }

    public function buildOrder()
    {
        $builtOrder = [];
        foreach ($this->_order as $key => $item) {
            if ($item instanceof Expression) {
                $builtOrder[] = $this->handleExpression($item);
            } else {
                if (is_string($key) && is_string($item)) {
                    $direction = strtoupper($item) == 'ASC' ? 'ASC' : 'DESC';
                    list($relation, $field) = $this->getRelationColumn($key);
                } elseif (is_string($item)) {
                    $column = $item;
                    $direction = 'ASC';
                    if (substr($column, 0, 1) == '-') {
                        $column = substr($column, 1);
                        $direction = 'DESC';
                    }
                    list($relation, $field) = $this->getRelationColumn($column);
                }
                $builtOrder[] = compact('relation', 'field', 'direction');
            }
        }
        return $builtOrder;
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

        if ($this->_order) {
            $this->_orderBy = $this->buildOrder();
        }

        return $this;
    }

    /**
     * Connect expression's relations (ex: {user__id}, {book__author__name}, etc)
     *
     * @param Expression $expression
     * @return Expression
     */
    public function handleExpression(Expression $expression)
    {
        if ($expression->getUseAliases() && ($aliases = $expression->getAliases())) {
            foreach ($aliases as $relationColumn) {
                $this->handleRelationColumn($relationColumn);
            }
        }
        return $expression;
    }

    public function getSelect()
    {
        return $this->_select;
    }

    public function getWhere()
    {
        return $this->_where;
    }

    public function getRelations()
    {
        return $this->_relations;
    }
}