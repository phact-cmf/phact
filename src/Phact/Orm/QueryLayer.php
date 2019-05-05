<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 14/04/16 08:24
 */

namespace Phact\Orm;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Exception;
use InvalidArgumentException;
use Phact\Helpers\SmartProperties;
use Phact\Orm\Aggregations\Aggregation;
use Phact\Orm\Having\Having;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;

/**
 * Class QueryLayer
 *
 * @property $model \Phact\Orm\Model
 * @property $querySet \Phact\Orm\QuerySet
 *
 * @package Phact\Orm
 */
class QueryLayer
{
    use SmartProperties;

    static $_columnAliases = [];

    protected $_query;

    protected $_key;

    /**
     * @var \Phact\Orm\QuerySet
     */
    protected $_querySet;

    protected $_aliases;

    /** @var Model */
    protected $_model;

    protected $_isBuiltQuerySet = false;

    protected $_paramsCounter = 0;

    /**
     * Prefix for values in query that required by SQL, but not requested by user
     * Eg: order columns, having attributes
     *
     * @var string
     */
    protected $_servicePrefix = '_service__';

    public function __construct($querySet, $key = null)
    {
        $this->_querySet = $querySet;
        $this->_key = $key;
    }

    /**
     * @return \Phact\Orm\QuerySet
     */
    public function getQuerySet()
    {
        if (!$this->_isBuiltQuerySet) {
            $this->_querySet->build();
            $this->_isBuiltQuerySet = true;
            $this->setAliases();
        }
        return $this->_querySet;
    }

    /**
     * @return \Phact\Orm\Query
     */
    public function getQuery()
    {
        return $this->getModel()->getQuery();
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        if (!$this->_model) {
            $this->_model = $this->_querySet->getModel();
        }
        return $this->_model;
    }

    public function getMetaData()
    {
        return $this->getModel()->getMetaData();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->getModel()->getTableName();
    }

    public function getQueryBuilderRaw()
    {
        return $this->getQuery()->getQueryBuilder();
    }

    /**
     * @return DBALQueryBuilder
     */
    public function getQueryBuilder()
    {
        $qb = $this->getQueryBuilderRaw();
        return $qb->from($this->quote($this->getTableName()));
    }

    public function quoteValue($value)
    {
        return $this->getQuery()->getConnection()->quote($value);
    }

    public function quote($value)
    {
        return $this->getQuery()->getConnection()->quoteIdentifier($value);
    }

    public function setAliases()
    {
        $this->_aliases = [];
        $relations = $this->getQuerySet()->getRelations();
        foreach ($relations as $relationName => $relation) {
            if (isset($relation['joins']) && is_array($relation['joins'])) {
                foreach ($relation['joins'] as $join) {
                    if (is_array($join) && isset($join['table'])) {
                        $tableName = $join['table'];
                        $this->setAlias($relationName, $tableName);
                    }
                }
            }
        }
    }

    public function getAliases()
    {
        return $this->_aliases;
    }

    public function setAlias($relationName, $tableName)
    {
        $this->_aliases[$relationName . '#' . $tableName] = $tableName . '_' . (count($this->_aliases) + 1);
    }

    public function getAlias($relationName, $tableName)
    {
        if (isset($this->_aliases[$relationName . '#' . $tableName])) {
            return $this->_aliases[$relationName . '#' . $tableName];
        }
        return null;
    }

    public function getTableOrAlias($relationName, $tableName)
    {
        if ($alias = $this->getAlias($relationName, $tableName)) {
            return $alias;
        }
        return $tableName;
    }

    /**
     * @param $relationName
     * @return Model
     */
    public function getRelationModel($relationName)
    {
        $relation = $this->getQuerySet()->getRelation($relationName);
        /** @var $model Model */
        return isset($relation['model']) ? $relation['model'] : null;
    }

    public function getRelationTable($relationName)
    {
        $model = $this->getRelationModel($relationName);
        if ($model) {
            return $model->getTableName();
        } else {
            /** Raw relation */
            $relation = $this->getQuerySet()->getRelation($relationName);
            if (isset($relation['joins'][0]['table'])) {
                return $relation['joins'][0]['table'];
            }
        }
        return null;
    }

    public function relationColumnAlias($column)
    {
        list($relationName, $attribute) = $this->getQuerySet()->getRelationColumn($column);
        return $this->columnAlias($relationName, $attribute, null);
    }

    public function relationColumnAttribute($relationName, $attribute)
    {
        $model = $this->getRelationModel($relationName);
        /** Raw relation */
        if (!$model) {
            return $attribute;
        }
        if ($field = $model->fetchField($attribute)) {
            return $field->getAttributeName();
        } else {
            throw new InvalidArgumentException(strtr("Invalid attribute name {attribute} for relation {relation}", [
                '{attribute}' => $attribute,
                '{relation}' => $relationName
            ]));
        }
    }

    public function columnAlias($relationName, $attribute, $tableName = null)
    {
        $key = implode('-', [get_class($this->getQuery()->getConnection()->getDatabasePlatform()), $this->_model->className(), $relationName, $attribute, $tableName]);
        if (!isset(static::$_columnAliases[$key])) {
            if ($attribute != '*'){
                $attribute = $this->relationColumnAttribute($relationName, $attribute);
            }
            $tableName = $this->getTableOrAlias($relationName, $tableName ?: $this->getRelationTable($relationName));
            static::$_columnAliases[$key] = $this->column($tableName, $attribute);
        }
        return static::$_columnAliases[$key];
    }

    public function column($tableName, $attribute)
    {
        $attribute = $this->isSafeAttribute($attribute) ? $attribute : $this->quote($attribute);
        return $this->quote($tableName) . '.' . $attribute;
    }

    public function isSafeAttribute($attribute)
    {
        return $attribute == '*';
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @return DBALQueryBuilder
     * @throws Exception
     */
    public function processJoins($queryBuilder)
    {
        $relations = $this->getQuerySet()->getRelations();

        foreach ($relations as $relationName => $relation) {
            // A relation and a table on which a join is to be build
            $currentRelationName = $this->getQuerySet()->parentRelationName($relationName);
            $currentTable = $this->getRelationTable($currentRelationName);
            $currentAlias = $this->getAlias($currentRelationName, $currentTable);

            if (isset($relation['joins']) && is_array($relation['joins'])) {


                foreach ($relation['joins'] as $join) {
                    if (is_array($join)) {
                        if (isset($join['table']) && isset($join['from']) && isset($join['to'])) {
                            $attributeFrom = $join['from'];
                            $attributeTo = $join['to'];

                            $tableName = $join['table'];

                            $alias = $this->getAlias($relationName, $tableName);
                            $fromColumn = $this->column($currentAlias ?: $currentTable, $attributeFrom);
                            $toColumn = $this->column($alias ?: $tableName, $attributeTo);

                            $attributes = [
                                $this->quote($currentAlias ?: $currentTable),
                                $this->quote($tableName),
                                $this->quote($alias),
                                $fromColumn . ' = ' . $toColumn
                            ];
                            $type = isset($join['type']) ? $join['type'] : 'left';
                            $typeMethod = mb_strtolower($type, 'UTF-8') . 'Join';

                            call_user_func_array([$queryBuilder, $typeMethod], $attributes);
                            // We change the current join
                            $currentTable = $tableName;
                            $currentRelationName = $relationName;
                            $currentAlias = $alias;
                        } else {
                            throw new Exception('Invalid join configuration. Please, check your relation fields.');
                        }
                    } elseif (is_string($join) && ($joinRelation = $this->getQuerySet()->getRelation($join))) {
                        /* @var $model Model */
                        $model = $joinRelation['model'];
                        $currentRelationName = $join;
                        $currentTable = $model->getTableName();
                        $currentAlias = $this->getAlias($currentRelationName, $currentTable);
                    }
                }
            }
        }
        return $queryBuilder;
    }

    /**
     * @param DBALQueryBuilder $queryBuilder
     * @param bool $buildOrder
     * @param bool $buildLimitOffset
     * @param bool $buildConditions
     * @param bool $buildGroup
     * @return DBALQueryBuilder
     * @throws Exception
     */
    public function buildQuery($queryBuilder, $buildOrder = true, $buildLimitOffset = true, $buildConditions = true, $buildGroup = true)
    {
        $qs = $this->getQuerySet();
        $queryBuilder = $this->processJoins($queryBuilder);
        if ($buildConditions) {
            $wheres = $this->processConditions($queryBuilder, $qs->getWhere(), 'and', true);
            if ($wheres) {
                $queryBuilder->where($wheres);
            }
        }
        if ($buildOrder) {
            $this->processOrder($queryBuilder, $qs->getOrderBy());
        }
        if ($buildGroup) {
            $this->processGroup($queryBuilder, $qs->getGroupBy());
            $this->processHaving($queryBuilder, $qs->getHaving());
        }
        if ($buildLimitOffset) {
            $this->processLimitOffset($queryBuilder, $qs->getLimit(), $qs->getOffset());
        }
        return $queryBuilder;
    }

    public function all($sql = false)
    {
        $queryBuilder = $this->getQueryBuilder();
        $qs = $this->getQuerySet();

        $select = $qs->getSelect();
        list($select, $bindings) = $this->buildSelect($select);
        if ($qs->getHasManyRelations()) {
            if (!$qs->getGroupBy() && $qs->getAutoGroup()) {
                $queryBuilder->select($select);
                $queryBuilder->groupBy($this->column($this->getTableName(), 'id'));
            } elseif ($qs->getAutoDistinct()) {
                reset($select);
                $first = key($select);
                $select[$first] = "DISTINCT {$select[$first]}";
                $queryBuilder->select($select);
            }
        } else {
            $queryBuilder->select($select);
        }
        $this->addBindings($queryBuilder, $bindings);
        $this->buildQuery($queryBuilder);
        if ($sql) {
            return $this->getSQL($queryBuilder);
        }
        $result = $queryBuilder->execute()->fetchAll();
        return $result;
    }

    public function rawAll($query, $bindings = [])
    {
        return $this->getQueryBuilder()->getConnection()->fetchAll($query, $bindings);
    }

    public function rawGet($query, $bindings = [])
    {
        return $this->getQueryBuilder()->getConnection()->fetchAssoc($query, $bindings);
    }

    public function get($sql = false)
    {
        $queryBuilder = $this->getQueryBuilder();
        $qs = $this->getQuerySet();

        $this->buildQuery($queryBuilder);

        $select = $qs->getSelect();
        list($select, $bindings) = $this->buildSelect($select);
        $queryBuilder->select($select);
        $this->addBindings($queryBuilder, $bindings);

        if ($sql) {
            return $this->getSQL($queryBuilder);
        }

        $result = $queryBuilder->execute()->fetch();
        return $result;
    }

    public function defaultSelect()
    {
        $select = [];
        $select[] = $this->column($this->getTableName(), '*');
        return array_merge($select, $this->withSelect());
    }

    public function withSelect()
    {
        $select = [];
        foreach ($this->getQuerySet()->getWithFkRelations() as $relationName) {
            $table = $this->getRelationTable($relationName);
            $relationModel = $this->getRelationModel($relationName);
            $attributes = $relationModel->getFieldsManager()->getDbAttributesList();
            $alias = $this->getAlias($relationName, $table);
            foreach ($attributes as $attribute) {
                $select[$this->quote($relationName . '__' . $attribute)] = $this->column($alias, $attribute);
            }
        }
        return $select;
    }


    /**
     * @param $select
     * @return array
     */
    public function buildSelect($select)
    {
        $result = [];
        $bindings = [];
        if (!$select) {
            $select = $this->defaultSelect();
        }
        foreach ($select as $key => $value) {
            if ($value instanceof Expression) {
                list($query, $rawBindings) = $this->convertExpression($value);
                $value = $query;
                $bindings = array_merge($bindings, $rawBindings);
            }
            if ($value == '*') {
                $value = $this->column($this->getTableName(), '*');
            }
            if (is_string($key)) {
                $result[$key] = $value . " AS " . $key;
            } else {
                $result[$key] = $value;
            }
        }
        return [$result, $bindings];
    }

    public function aggregate(Aggregation $aggregation, $sql = false)
    {
        $queryBuilder = $this->getQueryBuilder();
        $this->buildQuery($queryBuilder, false);

        $field = $aggregation->getField();
        if (!$aggregation->getRaw()) {
            $field = $this->relationColumnAlias($field);
        }
        $queryBuilder->select($aggregation->getSql($field) . ' as aggregation');
        if ($sql) {
            return $this->getSQL($queryBuilder);
        }
        $item = $queryBuilder->execute()->fetch();
        if (isset($item['aggregation'])) {
            return $item['aggregation'];
        }
        return null;
    }

    public function update($data = [], $sql = false)
    {
        $queryBuilder = $this->getQueryBuilder();
        $this->buildQuery($queryBuilder, false, false, true, false);

        if ($this->getQuerySet()->hasRelations()) {
            $queryBuilder = $this->wrapQuery($queryBuilder);
        }

        $queryBuilder->update($this->getTableName());
        foreach ($data as $attribute => $value) {
            $column = $attribute;
            if ($value instanceof Expression) {
                list($value, $bindings) = $this->convertExpression($value);
                $this->addBindings($queryBuilder, $bindings);
                $queryBuilder->set($column, $value);
            } else {
                $placeholder = $queryBuilder->createNamedParameter($value);
                $queryBuilder->set($column, $placeholder);
            }
        }

        if ($sql) {
            return $this->getSQL($queryBuilder);
        }
        return $queryBuilder->execute();
    }

    public function delete($sql = false)
    {
        $queryBuilder = $this->getQueryBuilder();
        $this->buildQuery($queryBuilder, false, false, true, false);

        if ($this->getQuerySet()->hasRelations()) {
            $queryBuilder = $this->wrapQuery($queryBuilder);
        }

        $queryBuilder->delete($this->quote($this->getTableName()));
        if ($sql) {
            return $this->getSQL($queryBuilder);
        }
        return $queryBuilder->execute();
    }

    public function values($columns = [], $distinct = true, $sql = false)
    {
        $queryBuilder = $this->getQueryBuilder();
        $qs = $this->getQuerySet();
        if (!$columns) {
            $select = [$this->column($this->getTableName(), '*')];
        } else {
            $select = [];
            foreach ($columns as $key => $attribute) {
                $alias = is_string($key) ? $this->quote($key) : null;
                $item = null;
                if ($attribute instanceof Expression) {
                    list($item, $bindings) = $this->convertExpression($attribute);
                    $this->addBindings($bindings);
                } else {
                    $item = $this->relationColumnAlias($attribute);
                    if (!$alias && $attribute !== '*') {
                        $alias = $this->quote($attribute);
                    }
                }
                $select[] = $item . ($alias ? ' AS ' . $alias: '');
            }
        }
        if ($withSelect = $this->withSelect()) {
            foreach ($withSelect as $alias => $column) {
                $select[] = $column . ' AS ' . $alias;
            }
        }
        if ($qs->getHasManyRelations() && $distinct) {
            reset($select);
            $first = key($select);
            $select[$first] = "DISTINCT {$select[$first]}";
            $queryBuilder->select($select);
        } else {
            $queryBuilder->select($select);
        }
        $this->buildQuery($queryBuilder);
        if ($sql) {
            return $this->getSQL($queryBuilder);
        }
        $result = [];
        foreach ($queryBuilder->execute()->fetchAll() as $key => $row) {
            foreach ($row as $column => $value) {
                if (strpos($column, $this->_servicePrefix) === 0) {
                    unset($row[$column]);
                }
            }
            $result[$key] = $row;
        }
        return $result;
    }

    protected function createModel($modelClass, $dbData): Model
    {
        /** @var Model $model */
        $model = new $modelClass;
        $model->setDbData($dbData);
        return $model;
    }

    /**
     * Wrap query for update/delete
     * @param $queryBuilder DBALQueryBuilder
     * @return DBALQueryBuilder
     */
    public function wrapQuery($queryBuilder)
    {
        // Create a temporary table for correct operation with JOIN
        $queryUpdate = $this->getQueryBuilder();
        $queryWrapper = $this->getQueryBuilderRaw();

        $pk = $this->relationColumnAlias('pk');
        $pkAttribute = $this->relationColumnAttribute('__this', 'pk');

        $tempTable = 'temp_table_wrapper';

        $queryBuilder->select($pk);

        $subQueryFrom = $this->prepareSubQuery($queryBuilder, $queryUpdate);

        $queryWrapper
            ->from( '(' . $subQueryFrom . ') AS ' . $tempTable)
            ->select($this->column($tempTable, $pkAttribute));

        $subQueryIn = $this->prepareSubQuery($queryWrapper, $queryUpdate);

        $queryUpdate->where($pk . ' IN (' . $subQueryIn . ')');
        return $queryUpdate;
    }

    public function clearConditions($conditions)
    {
        if (is_array($conditions) && count($conditions) == 1) {
            return $this->clearConditions($conditions[0]);
        } else {
            return $conditions;
        }
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @param $conditions
     * @param string $operator
     * @param bool $clear
     * @return CompositeExpression
     */
    public function processConditions($queryBuilder, $conditions, $operator = 'and', $clear = false)
    {
        if ($clear) {
            $conditions = $this->clearConditions($conditions);
        }
        if (!is_array($conditions) || isset($conditions['relation'])) {
            $conditions = [$conditions];
        }
        if (isset($conditions[0]) && is_string($conditions[0]) && in_array(mb_strtolower($conditions[0], 'UTF-8'), ['not', 'and', 'or'])) {
            $operator = mb_strtolower(array_shift($conditions), 'UTF-8');
        }
        $result = [];
        foreach ($conditions as $key => $condition) {
            if (is_array($condition) && isset($condition['relation'])) {
                $lookupManager = $this->getQuerySet()->getLookupManager();
                $column = $this->columnAlias($condition['relation'], $condition['field']);
                $result[] = $lookupManager->processCondition($queryBuilder, $column, $condition['lookup'], $condition['value'], $operator);
            } elseif ($condition instanceof Expression) {
                list($expression, $bindings) = $this->convertExpression($condition);
                $this->addBindings($queryBuilder, $bindings);
                $result[] = $this->buildWhere($expression);
            } elseif (is_array($condition)) {
                $subConditions = $this->processConditions($queryBuilder, $condition, 'and', true);
                if ($subConditions) {
                    $result[] = $this->buildWhere($subConditions);
                }
            }
        }
        return $this->composeConditions($queryBuilder, $operator, $result);
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @param $operator
     * @param $conditions
     * @return CompositeExpression
     */
    public function composeConditions($queryBuilder, $operator, $conditions)
    {
        if (!$conditions) {
            return null;
        }
        $compositeExpression = $queryBuilder->expr()->andX();
        if ($operator == 'or') {
            $compositeExpression = $queryBuilder->expr()->orX();
        } elseif ($operator == 'not') {
            $compositeExpression = new WrappedCompositeExpression(CompositeExpression::TYPE_AND);
            $compositeExpression->setWrapper("NOT");
        }
        foreach ($conditions as $condition) {
            $key = $condition['key'];
            $value = $condition['value'];
            $valueExpression = false;
            if ($key instanceof Expression) {
                list($key, $bindings) = $this->convertExpression($key);
                $this->addBindings($queryBuilder, $bindings);
            }
            if ($value instanceof Expression) {
                $valueExpression = true;
                list($value, $bindings) = $this->convertExpression($value);
                $this->addBindings($queryBuilder, $bindings);
            }
            if (is_null($condition['operator']) && is_null($value)) {
                $compositeExpression->add($key);
            } else {
                if ($valueExpression) {
                    $comparison = $queryBuilder->expr()->comparison($key, $condition['operator'], $value);
                } elseif ($condition['operator'] == 'IN') {
                    if (is_array($value)) {
                        $placeholders = [];
                        foreach ($value as $item) {
                            $placeholders[] = $queryBuilder->createNamedParameter($item);
                        }
                        $value = implode(',', $placeholders);
                    }
                    $comparison = $queryBuilder->expr()->comparison($key, $condition['operator'], '(' . $value . ')');
                } elseif (is_array($value) && count($value) == 2 && $condition['operator'] == 'BETWEEN') {
                    $placeholder1 = $queryBuilder->createNamedParameter($value[0]);
                    $placeholder2 = $queryBuilder->createNamedParameter($value[1]);
                    $comparison = $queryBuilder->expr()->comparison($key, $condition['operator'], "{$placeholder1} AND {$placeholder2}");
                } else {
                    $placeholder = $queryBuilder->createNamedParameter($value);
                    $comparison = $queryBuilder->expr()->comparison($key, $condition['operator'], $placeholder);
                }
                $compositeExpression->add($comparison);
            }
        }

        return $compositeExpression;
    }

    public static function buildWhere($key, $operator = null, $value = null, $joiner = 'AND')
    {
        return [
            'key' => $key,
            'operator' => $operator,
            'value' => $value,
            'joiner' => $joiner
        ];
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @param $order array
     * @return array
     */
    public function processOrder($queryBuilder, $order)
    {
        foreach ($order as $item) {
            if ($item instanceof Expression) {
                list($value, $bindings) = $this->convertExpression($item);
                $queryBuilder->add('orderBy', $value, true);
                $this->addBindings($queryBuilder, $bindings);
            } elseif (is_array($item)) {
                $column = $this->columnAlias($item['relation'], $item['field']);
                if ($this->getQuerySet()->getHasManyRelations()) {
                    $alias = $this->_servicePrefix . implode('__', ['order', $item['relation'], $item['field']]);
                    $alias = $this->quote($alias);
                    $queryBuilder->add('select', "{$column} AS {$alias}", true);
                    $queryBuilder->addOrderBy($alias, $item['direction']);
                } else {
                    $queryBuilder->addOrderBy($column, $item['direction']);
                }
            }
        }
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @param $group array
     */
    public function processGroup($queryBuilder, $group)
    {
        foreach ($group as $item) {
            if ($item instanceof Expression) {
                list($value, $bindings) = $this->convertExpression($item);
                $queryBuilder->addGroupBy($value);
                $this->addBindings($queryBuilder, $bindings);
            } elseif (is_array($item)) {
                $queryBuilder->addGroupBy($this->columnAlias($item['relation'], $item['field']));
            }
        }
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @param $having Expression|Having
     * @throws Exception
     */
    public function processHaving($queryBuilder, $having)
    {
        if ($having instanceof Expression) {
            list($value, $bindings) = $this->convertExpression($having);
            $queryBuilder->having($value);
            $this->addBindings($queryBuilder, $bindings);
        } elseif ($having instanceof Having) {
            $aggregation = $having->getAggregation();
            $field = $aggregation->getField();
            $name = $this->_servicePrefix . implode('__', ['having']);
            if (!$aggregation->getRaw()) {
                $field = $this->relationColumnAlias($field);
            }
            $queryBuilder->add('select', $aggregation->getSql($field) . ' as ' . $name, true);
            $queryBuilder->having($aggregation->getSql($field) . ' ' . $having->getCondition());
            if (!$queryBuilder->getQueryPart('groupBy')) {
                $queryBuilder->groupBy($this->columnAlias(null, $this->getModel()->getPkAttribute()));
            }
        }
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @param $limit int|null
     * @param $offset int|null
     */
    public function processLimitOffset($queryBuilder, $limit, $offset)
    {
        if (isset($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        if (isset($offset)) {
            $queryBuilder->setFirstResult($offset);
        }
    }

    /**
     * @param Expression $expression
     * @return array
     */
    public function convertExpression(Expression $expression)
    {
        $value = $expression->getExpression();
        $params = $expression->getParams();
        $bindings = [];

        if ($expression->getUseAliases() && ($aliases = $expression->getAliases())) {
            $replaces = [];
            foreach ($aliases as $relationColumn) {
                $column = $this->relationColumnAlias($relationColumn);
                $replaces['{' . $relationColumn . '}'] = $column;
            }
            $value = strtr($value, $replaces);
            if ($params) {
                $counter = 0;
                $value = preg_replace_callback('/\?/', function ($matches) use ($counter, &$bindings, $params) {
                    if (isset($params[$counter])) {
                        $name = $this->fetchBindingName();
                        $bindings[$name] = $params[$counter];
                        $counter++;
                        return ':' . $name;
                    }
                    return $matches[0];
                }, $value);

                foreach ($params as $name => $param) {
                    if (is_string($name)) {
                        $bindings[$name] = $param;
                    }
                }
            }
        }
        return [$value, $bindings];
    }

    public function fetchBindingName()
    {
        $name = 'query_param__' . $this->_paramsCounter;
        $this->_paramsCounter++;
        return $name;
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @param array $bindings
     */
    public function addBindings($queryBuilder, $bindings = [])
    {
        foreach ($bindings as $name => $value) {
            $queryBuilder->setParameter($name, $value);
        }
    }

    public function getCacheKey($type)
    {
        return $this->_key . '#' . $type;
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @return string
     */
    public function getSQL($queryBuilder)
    {
        return $this->getQuery()->getSQL($queryBuilder);
    }

    /**
     * @param $srcQueryBuilder DBALQueryBuilder
     * @param $dstQueryBuilder DBALQueryBuilder
     * @return string
     */
    public function prepareSubQuery($srcQueryBuilder, $dstQueryBuilder)
    {
        return $this->getQuery()->prepareSubQuery($srcQueryBuilder, $dstQueryBuilder);
    }
}