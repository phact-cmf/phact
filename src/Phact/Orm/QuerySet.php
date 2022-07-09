<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 14/04/16 07:53
 */

namespace Phact\Orm;

use InvalidArgumentException;
use Phact\Helpers\SmartProperties;
use Phact\Orm\Aggregations\Aggregation;
use Phact\Orm\Aggregations\Count;
use Phact\Orm\Aggregations\Max;
use Phact\Orm\Aggregations\Min;
use Phact\Orm\Aggregations\Avg;
use Phact\Orm\Aggregations\Sum;
use Phact\Orm\Fields\ForeignField;
use Phact\Orm\Fields\ManyToManyField;
use Phact\Orm\Fields\RelationField;
use Phact\Orm\Having\Having;
use Phact\Pagination\PaginableInterface;

/**
 * Class QuerySet
 *
 * @property $queryLayer QueryLayer
 * @property $model Model
 *
 * @package Phact\Orm
 */
class QuerySet implements PaginableInterface, QuerySetInterface
{
    use SmartProperties;

    static $_lookupManager;

    /**
     * @var Model
     */
    protected $_model;

    /**
     * @var string
     */
    protected $_modelClass;

    protected $_queryLayer;

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

    /**
     * Built group
     * @var array
     */
    protected $_group = [];

    /**
     * Built group
     * @var array
     */
    protected $_groupBy = [];

    /**
     * Auto group
     * @var bool
     */
    protected $_autoGroup = false;

    /**
     * Auto distinct
     * @var bool
     */
    protected $_autoDistinct = true;

    protected $_select = [];

    /**
     * Built filter and exclude
     * @var array
     */
    protected $_where = [];
    protected $_relations = [];

    /**
     * @var Expression|Having|null
     */
    protected $_having = null;

    /**
     * Limit and offset
     * @var int|null
     */
    protected $_limit = null;
    protected $_offset = null;

    protected $_hasManyRelations = false;

    /**
     * @var Aggregation|null
     */
    protected $_aggregation = null;

    /**
     * With relations
     *
     * @var With[]
     */
    protected $_with = [];

    /**
     * With FK relations names
     * @var string[]
     */
    protected $_withFk = [];

    protected $_aliases = [];

    /**
     * @return QuerySet
     */
    protected function nextQuerySet()
    {
        return clone $this;
    }

    public function getQuerySet(): QuerySet
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
        $this->setModelClass(get_class($model));
    }

    public function setModelClass($class)
    {
        $this->_modelClass = $class;
    }

    public function getModelClass()
    {
        return $this->_modelClass;
    }

    public function getQueryLayer()
    {
        return new QueryLayer($this->nextQuerySet(), $this->nextQuerySet()->buildKey());
    }

    /**
     * @return LookupManager
     */
    public function getLookupManager()
    {
        if (!self::$_lookupManager) {
            self::$_lookupManager = new LookupManager();
        }
        return self::$_lookupManager;
    }

    public function setLookupManager($lookup)
    {
        self::$_lookupManager = $lookup;
    }

    public function createModel($row, $modelClass = null)
    {
        $modelClass = $modelClass ?: $this->_modelClass;
        /* @var $model Model */
        $model = new $modelClass;
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

    public function raw($query, $params = [])
    {
        return $this->getQueryLayer()->rawAll($query, $params);
    }

    public function all()
    {
        $data = $this->getQueryLayer()->all();
        $this->postProcessing($data, true);
        $this->makeModels($data);
        return $data;
    }

    public function allSql()
    {
        return $this->getQueryLayer()->all(true);
    }

    public function rawAll($query, $params = [])
    {
        $data = $this->getQueryLayer()->rawAll($query, $params);
        return $this->createModels($data);
    }

    /**
     * @return null|Model
     */
    public function get()
    {
        $row = $this->getQueryLayer()->get();
        return $row ? $this->createModel($row) : null;
    }

    public function rawGet($query, $params = [])
    {
        $row = $this->getQueryLayer()->rawGet($query, $params);
        return $row ? $this->createModel($row) : null;
    }

    public function getSql()
    {
        return $this->getQueryLayer()->get(true);
    }

    public function getAggregation()
    {
        return $this->_aggregation;
    }

    public function aggregate(Aggregation $aggregation)
    {
        $qs = $this->nextQuerySet();
        $qs->_aggregation = $aggregation;
        return $qs->getQueryLayer()->aggregate($aggregation);
    }

    public function aggregateSql(Aggregation $aggregation)
    {
        $qs = $this->nextQuerySet();
        $qs->_aggregation = $aggregation;
        return $qs->getQueryLayer()->aggregate($aggregation, true);
    }

    public function values($columns = [], $flat = false, $distinct = true)
    {
        $this->handleRelationColumns($columns);
        $data = $this->getQueryLayer()->values($columns, $distinct);
        if ($flat) {
            $result = [];
            foreach ($data as $row) {
                foreach ($row as $value) {
                    $result[] = $value;
                }
            }
            return $result;
        }
        $this->postProcessing($data, false);
        return $data;
    }

    public function valuesSql($columns = [], $flat = false, $distinct = true)
    {
        $this->handleRelationColumns($columns);
        return $this->getQueryLayer()->values($columns, $distinct, true);
    }

    public function update($data = [])
    {
        foreach ($data as $key => $item) {
            if ($item instanceof Expression) {
                $this->handleExpression($item);
            }
        }
        return $this->getQueryLayer()->update($data);
    }

    public function updateSql($data = [])
    {
        return $this->getQueryLayer()->update($data, true);
    }

    public function delete()
    {
        return $this->getQueryLayer()->delete();
    }

    public function deleteSql()
    {
        return $this->getQueryLayer()->delete(true);
    }

    public function count()
    {
        return $this->aggregate(new Count());
    }

    public function countSql()
    {
        return $this->aggregateSql(new Count());
    }

    public function max($attribute)
    {
        return $this->aggregate(new Max($attribute));
    }

    public function maxSql($attribute)
    {
        return $this->aggregateSql(new Max($attribute));
    }

    public function min($attribute)
    {
        return $this->aggregate(new Min($attribute));
    }

    public function minSql($attribute)
    {
        return $this->aggregateSql(new Min($attribute));
    }

    public function avg($attribute)
    {
        return $this->aggregate(new Avg($attribute));
    }

    public function avgSql($attribute)
    {
        return $this->aggregateSql(new Avg($attribute));
    }

    public function sum($attribute)
    {
        return $this->aggregate(new Sum($attribute));
    }

    public function sumSql($attribute)
    {
        return $this->aggregateSql(new Sum($attribute));
    }

    public function choices($key, $value)
    {
        if (!is_string($key) && !is_string($value)) {
            throw new InvalidArgumentException('QuerySet::choices() accept only strings as $key and $value attributes');
        }
        $choices = [];
        $data = $this->values([$key, $value]);
        foreach ($data as $row) {
            $choices[$row[$key]] = $row[$value];
        }
        return $choices;
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
        if (isset($filter) && !empty($filter)) {
            $this->_filter[] = $filter;
        }
        return $this->nextQuerySet();
    }

    /**
     * @param array $exclude
     * @return QuerySet
     */
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

    /**
     * @param array $order
     * @return QuerySet
     */
    public function order($order = [])
    {
        if (is_string($order) || $order instanceof Expression) {
            $order = [$order];
        } elseif (!is_array($order)) {
            throw new InvalidArgumentException('QuerySet::order() accept only arrays, strings or Expression objects');
        }
        $this->_order = array_merge($this->_order, $order);
        return $this->nextQuerySet();
    }

    /**
     * @param array $group
     * @return QuerySet
     */
    public function group($group = [])
    {
        if (is_string($group)) {
            $group = [$group];
        } elseif (!is_array($group)) {
            throw new InvalidArgumentException('QuerySet::group() accept only arrays or strings');
        }
        $this->_group = array_merge($this->_group, $group);
        return $this->nextQuerySet();
    }

    public function getGroup()
    {
        return $this->_group;
    }

    public function getGroupBy()
    {
        return $this->_groupBy;
    }

    public function setAutoGroup($autoGroup)
    {
        $this->_autoGroup = $autoGroup;
        return $this;
    }

    public function getAutoGroup()
    {
        return $this->_autoGroup;
    }

    public function setAutoDistinct($autoDistinct)
    {
        $this->_autoDistinct = $autoDistinct;
        return $this;
    }

    public function getAutoDistinct()
    {
        return $this->_autoDistinct;
    }

    public function having($expression)
    {
        $this->_having = $expression;
        return $this->nextQuerySet();
    }

    public function getHaving()
    {
        return $this->_having;
    }

    /**
     * @param $limit
     * @return QuerySet
     */
    public function limit($limit)
    {
        $this->_limit = $limit;
        return $this->nextQuerySet();
    }

    public function offset($offset)
    {
        $this->_offset = $offset;
        return $this->nextQuerySet();
    }

    public function getLimit()
    {
        return $this->_limit;
    }

    public function getOffset()
    {
        return $this->_offset;
    }

    public function appendRelation($name, $model, $joins = []): QuerySet
    {
        $name = $this->stringRelationPath($name);
        $this->_relations[$name] = [
            'model' => $model,
            'joins' => $joins
        ];
        return $this;
    }

    public function searchRelation($name)
    {
        $path = $this->arrayRelationPath($name);

        $foundName = [];
        $searchName = [];
        $nextName = [];

        $model = $this->getModel();

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

    public function cleanRelationName($relationName)
    {
        $pos = strpos($relationName, '#');
        if ($pos !== false) {
            return substr($relationName, 0, $pos);
        }
        return $relationName;
    }

    public function connectRelation($name)
    {
        /* @var $model Model */
        list($model, $path, $found) = $this->searchRelation($name);
        $full = $found;

        foreach ($path as $relationName) {
            $cleanName = $this->cleanRelationName($relationName);
            $full[] = $relationName;
            /* @var $field \Phact\Orm\Fields|RelationField */
            if (($field = $model->getField($cleanName)) && is_a($field, RelationField::class)) {
                if (is_a($field, ManyToManyField::class) && ($throughName = $field->getThroughName())) {
                    $throughRelationPath = $this->siblingRelationPath($full, $throughName);
                    if (!$this->hasRelation($throughRelationPath)) {
                        $this->connectRelation($throughRelationPath);
                    }
                }
                if ($field->getIsMany()) {
                    $this->_hasManyRelations = $field->getIsMany();
                }
                $model = $field->getRelationModel();
                $this->appendRelation($full, $model, $field->getRelationJoins());
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
        if (!isset($this->_relations[$name])) {
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
        } elseif ($value instanceof QuerySetInterface) {
            $value = new SqlExpression("({$value->allSql()})");
        }elseif ($value instanceof Model) {
            $value = $value->getPk();
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

    public function handleRelationColumns($columns) {
        foreach ($columns as $column) {
            if ($column instanceof Expression) {
                $this->handleExpression($column);
            } else {
                $this->handleRelationColumn($column);
            }
        }
    }

    public function buildConditions($data)
    {
        $conditions = [];
        foreach ($data as $key => $condition) {
            if ($key === 0 && in_array($condition, ['not', 'and', 'or'])) {
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

    public function buildWith()
    {
        $this->_withFk = $this->buildWithFkRelations($this->getModel(), $this->getWith());
        $this->_withFk = array_unique($this->_withFk);
        foreach ($this->getWithFkRelations() as $relation) {
            $this->connectRelation($relation);
        }
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
                    list($relation, $field) = $this->handleRelationColumn($key);
                } elseif (is_string($item)) {
                    $column = $item;
                    $direction = 'ASC';
                    if (substr($column, 0, 1) == '-') {
                        $column = substr($column, 1);
                        $direction = 'DESC';
                    }
                    list($relation, $field) = $this->handleRelationColumn($column);
                }
                $builtOrder[] = compact('relation', 'field', 'direction');
            }
        }
        return $builtOrder;
    }

    public function buildGroup()
    {
        $builtGroup = [];
        foreach ($this->_group as $item) {
            if (is_string($item)) {
                list($relation, $field) = $this->handleRelationColumn($item);
                $builtGroup[] = compact('relation', 'field');
            } elseif ($item instanceof Expression) {
                $builtGroup[] = $this->handleExpression($item);
            }
        }
        return $builtGroup;
    }

    public function build()
    {
        if ($this->_with) {
            $this->buildWith();
        }
        if ($this->_aggregation) {
            $this->handleAggregation($this->_aggregation);
        }
        if ($this->_having) {
            $this->handleHaving($this->_having);
        }
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
            $this->_where = Q::andQ([$filter, $exclude]);
        }
        if ($this->_order) {
            $this->_orderBy = $this->buildOrder();
        }
        if ($this->_group) {
            $this->_groupBy = $this->buildGroup();
        }
        return $this;
    }

    public function buildKey()
    {
        return $this->getModelClass() . '#' . md5(serialize(array_merge(
            $this->_with,
            [$this->_aggregation],
            $this->_filter,
            $this->_exclude,
            $this->_order,
            [$this->_limit, $this->_offset]
        )));
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

    public function handleAggregation(Aggregation $aggregation)
    {
        $field = $aggregation->getField();
        $raw = $aggregation->getRaw();
        if (!$raw) {
            $this->handleRelationColumn($field);
        }
    }

    public function handleHaving($having)
    {
        if ($having instanceof Expression) {
            $this->handleExpression($having);
        } elseif ($having instanceof Having) {
            $aggregation = $having->getAggregation();

            $field = $aggregation->getField();
            $raw = $aggregation->getRaw();

            if (!$raw) {
                $this->handleRelationColumn($field);
            }
        }
    }

    public function getSelect()
    {
        return $this->_select;
    }

    public function select($select = [])
    {
        if (is_string($select) || $select instanceof Expression) {
            $select = [$select];
        } elseif (!is_array($select)) {
            throw new InvalidArgumentException('QuerySet::select() accept only arrays, strings or Expression objects');
        }
        $this->_select = array_merge($this->_select, $select);
        return $this->nextQuerySet();
    }

    public function getWhere()
    {
        return $this->_where;
    }

    public function getRelations()
    {
        return $this->_relations;
    }

    public function hasRelations()
    {
        return count($this->_relations) > 0;
    }

    /**
     * @param string[] $with
     * @return $this
     */
    public function with($with = [])
    {
        $this->_with = array_merge($this->_with, $this->normalizeWith($with));
        return $this;
    }

    /**
     * @return With[]
     */
    public function getWith()
    {
        return $this->_with;
    }

    protected function normalizeWith($with = [])
    {
        $normalized = [];
        foreach ($with as $item) {
            if ($item instanceof With) {
                $normalized[] = $item;
            } elseif (is_string($item)) {
                $exploded = explode('__', $item);
                $prevWith = null;
                $newWith = null;
                foreach (array_reverse($exploded) as $withItem) {
                    $withItemExploded = explode('->', $withItem);
                    $relationName = $withItemExploded[0];
                    $namedSelection = $withItemExploded[1] ?? null;
                    $newWith = new With($relationName);
                    if ($namedSelection) {
                        $newWith->setNamedSelection($namedSelection);
                    }
                    if ($prevWith) {
                        $newWith->setWith([$prevWith]);
                    }
                    $prevWith = $newWith;
                }
                $normalized[] = $newWith;
            }
        }
        return $normalized;
    }

    public function getWithFkRelations()
    {
        return $this->_withFk;
    }

    /**
     * @param $with With[]
     * @return array
     */
    public function buildWithFkRelations(Model $model, array $with = [], $prefix = '')
    {
        $relations = [];
        foreach ($with as $item) {
            if (
                !$item->isPrefetch() &&
                ($field = $model->getField($item->getRelationName())) &&
                ($field instanceof ForeignField) &&
                ($relationModel = $field->getRelationModel())
            ) {
                $relationName = $prefix . $item->getRelationName();
                $relations[] = $relationName;
                $subRelations = $this->buildWithFkRelations($relationModel, $item->getWith(), $relationName . '__');
                if ($subRelations) {
                    $relations = array_merge($relations, $subRelations);
                }
            }
        }
        return $relations;
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

    protected function withValues($columns = [], $makeModels = false, $distinct = true)
    {
        $this->handleRelationColumns($columns);
        $data = $this->getQueryLayer()->values($columns, $distinct);
        $this->postProcessing($data, $makeModels);
        return $data;
    }

    protected function makeModels(&$data)
    {
        foreach ($data as &$ownerModel) {
            $ownerModel = $this->createModel($ownerModel);
        }
    }

    protected function postProcessing(&$data, $makeModels = false)
    {
        $with = $this->getQuerySet()->getWith();
        $this->postProcessingWith($data, $data, $this->getModel(), $makeModels, $with);
    }

    /**
     * @param With[] $with
     */
    protected function postProcessingWith(&$rawFetch, &$ownerModels, $model, $makeModels = false, array $with = [], array $path = [])
    {
        foreach ($with as $item) {
            /** @var RelationField $field */
            if ($field = $model->getField($item->getRelationName())) {

                if (($field instanceof ForeignField) && $item->isSelect()) {
                    // Handle foreign connections with select
                    $this->postProcessingWithSelect($item, $path, $rawFetch, $ownerModels, $field, $makeModels);
                } elseif (($field instanceof FieldManagedInterface) && ($manager = $field->getManager()) && ($manager instanceof RelationBatchInterface)) {
                    // Handle prefetch
                    $this->postProcessingWithPrefetch($item, $field, $manager, $ownerModels, $makeModels);
                }
            }
        }
    }

    /**
     * @param $with With
     * @param $relationName
     * @param $rawFetch
     * @param $ownerModels
     * @param $field ForeignField
     * @param $makeModels
     * @param $path
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    protected function postProcessingWithSelect($with, $path, &$rawFetch, &$ownerModels, $field, $makeModels)
    {
        $relationModel = $field->getRelationModel();
        $relationModelClass = $field->getRelationModelClass();

        $path[] = $with->getRelationName();
        $relationName = implode('__', $path);

        $len = \strlen($relationName . '__');

        $fieldsMap = [];
        $data = [];

        // Fetch model data from existing fetch
        foreach ($rawFetch as &$row) {
            $withModel = [];
            if (!$fieldsMap) {
                foreach ($row as $name => $value) {
                    if (
                        \substr($name, 0, $len) === $relationName . '__' &&
                        ($fieldName = str_replace($relationName . '__', '', $name)) &&
                        (\strpos($fieldName, '__') === false)
                    ) {
                        $fieldsMap[$name] = $fieldName;
                    }
                }
            }
            foreach($fieldsMap as $name => $fieldName) {
                $withModel[$fieldName] = $row[$name];
                unset($row[$name]);
            }
            $data[] = $withModel;
        }

        // Process sub-with
        $this->postProcessingWith($rawFetch, $data, $relationModel, $makeModels, $with->getWith(), $path);

        $withKey = $with->getKey();
        foreach ($ownerModels as $i => &$ownerModel) {
            // Fill models
            $ownerModel[$withKey] = $makeModels ? $this->createModel($data[$i], $relationModelClass) : $data[$i];
        }
    }

    /**
     * @param $with With
     * @param $field RelationField
     * @param $manager RelationManager|RelationBatchInterface|QuerySetInterface
     * @param $ownerModels
     * @param bool $makeModels
     * @throws \Phact\Exceptions\InvalidConfigException
     */
    protected function postProcessingWithPrefetch($with, $field, $manager, &$ownerModels, $makeModels = false)
    {
        $relationModel = $field->getRelationModel();
        $relationModelClass = $field->getRelationModelClass();

        $outerAttribute = $manager->getOuterAttribute();
        $outerIds = [];
        foreach ($ownerModels as $ownerModelData) {
            if (isset($ownerModelData[$outerAttribute])) {
                $outerIds[] = $ownerModelData[$outerAttribute];
            }
        }
        /** @var QuerySetInterface $qs */
        $qs = $manager->filterBatch($outerIds)->with($with->getWith());
        if ($name = $with->getNamedSelection()) {
            $qs = $qs->processNamedSelection($name);
        }
        $values = $with->getValues() ?: ['*'];
        $additonalAttributes = $manager->getAdditionalAttributes();
        $hasAttributes = \count($additonalAttributes) > 0;

        $values = array_merge($values, $additonalAttributes);

        $data = $qs->getQuerySet()->withValues($values, $makeModels);

        $innerAttribute = $manager->getInnerAttribute();
        $innerField = $relationModel->fetchField($innerAttribute);
        if ($innerField && property_exists($field, 'from')) {
            $innerAttribute = $field->from ?: $innerAttribute;
        }

        $outerAttribute = $manager->getOuterAttribute();

        // Skip if columns are undefined or empty data
        if (\count($data) > 0) {
            if (!isset($ownerModels[0][$outerAttribute])) {
                return;
            }
            if (!isset($data[0][$innerAttribute])) {
                return;
            }
        }

        // Matching
        $withKey = $with->getKey();

        $map = [];
        foreach ($ownerModels as $i => &$ownerModel) {
            $map[$ownerModel[$outerAttribute]] = $i;
        }
        foreach ($data as $dataKey => $dataItem) {
            $i = $map[$dataItem[$innerAttribute]] ?? null;
            if ($i !== null) {
                if ($hasAttributes) {
                    $cleanItem = [];
                    foreach ($dataItem as $key => $value) {
                        if (!\in_array($key, $additonalAttributes)) {
                            $cleanItem[$key] = $value;
                        }
                    }
                    $dataItem = $cleanItem;
                }
                if ($field instanceof ForeignField) {
                    $ownerModels[$i][$withKey] = $makeModels ? $this->createModel($dataItem, $relationModelClass) : $dataItem;
                } else {
                    $ownerModels[$i][$withKey][] = $makeModels ? $this->createModel($dataItem, $relationModelClass) : $dataItem;
                }
            }
        }
    }
}