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

use Exception;
use InvalidArgumentException;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Aggregations\Aggregation;
use Phact\Orm\Having\Having;
use Phact\Orm\Raw;

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

    public function getQueryBuilder()
    {
        $qb = $this->getQueryBuilderRaw();
        return $qb->table([$this->getTableName()]);
    }

    public function getQueryAdapter()
    {
        return $this->getQuery()->getAdapter();
    }

    public function sanitize($value)
    {
        return $this->getQueryAdapter()->wrapSanitizer($value);
    }

    public function setAliases()
    {
        $this->_aliases = [];
        $tables = [$this->getTableName() => '__this'];
        $relations = $this->getQuerySet()->getRelations();
        foreach ($relations as $relationName => $relation) {
            if (isset($relation['joins']) && is_array($relation['joins'])) {
                foreach ($relation['joins'] as $join) {
                    if (is_array($join) && isset($join['table'])) {
                        $tableName = $join['table'];
                        if (isset($tables[$tableName])) {
                            $this->setAlias($relationName, $tableName);
                        } else {
                            $tables[$join['table']] = $relationName;
                        }
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

    public function relationColumnAlias($column, $addTablePrefix = false)
    {
        list($relationName, $attribute) = $this->getQuerySet()->getRelationColumn($column);
        return $this->columnAlias($relationName, $attribute, null, $addTablePrefix);
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

    public function columnAlias($relationName, $attribute, $tableName = null, $addTablePrefix = false)
    {
        $key = implode('-', [$this->_model->className(), $relationName, $attribute, $tableName]);
        if (!isset(static::$_columnAliases[$key])) {
            if ($attribute != '*'){
                $attribute = $this->relationColumnAttribute($relationName, $attribute);
            }
            $tableName = $this->getTableOrAlias($relationName, $tableName ?: $this->getRelationTable($relationName));
            static::$_columnAliases[$key] = $this->column($tableName, $attribute, $addTablePrefix);
        }
        return static::$_columnAliases[$key];
    }

    public function column($tableName, $attribute, $addTablePrefix = false)
    {
        if ($addTablePrefix) {
            $tableName = $this->getQueryBuilderRaw()->addTablePrefix($tableName);
        }
        return $tableName . '.' . $attribute;
    }

    /**
     * @param $query QueryBuilder
     * @return QueryBuilder
     * @throws Exception
     */
    public function processJoins($query)
    {
        $relations = $this->getQuerySet()->getRelations();
        foreach ($relations as $relationName => $relation) {
            // A relation and a table on which a join is to be build
            $currentRelationName = $this->getQuerySet()->parentRelationName($relationName);
            $currentTable = $this->getRelationTable($currentRelationName);
            $currentAlias = null;

            if (isset($relation['joins']) && is_array($relation['joins'])) {
                foreach ($relation['joins'] as $join) {
                    if (is_array($join)) {
                        if (isset($join['table']) && isset($join['from']) && isset($join['to'])) {
                            $attributeFrom = $join['from'];
                            $attributeTo = $join['to'];

                            $tableName = $join['table'];
                            $connectTable = $tableName;

                            if ($alias = $this->getAlias($relationName, $tableName)) {
                                $aliasedTable = $query->addTablePrefix($tableName);
                                $connectTable = $this->sanitize($aliasedTable) . ' AS ' . $this->sanitize($alias);
                                $connectTable = $query->raw($connectTable);
                            }

                            $query->join(
                                $connectTable,
                                $this->column($currentAlias ?: $currentTable, $attributeFrom),
                                '=',
                                $this->column($alias ?: $tableName, $attributeTo),
                                isset($join['type']) ? $join['type'] : 'left'
                            );

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
                    }
                }
            }
        }
        return $query;
    }

    public function buildQuery($query, $buildOrder = true, $buildLimitOffset = true, $buildConditions = true, $buildGroup = true)
    {
        $qs = $this->getQuerySet();
        $query = $this->processJoins($query);
        if ($buildConditions) {
            $wheres = $this->buildConditions($query, $qs->getWhere(), 'and', true);
            $query->setWhere($wheres);
        }
        if ($buildOrder) {
            $this->buildOrder($query, $qs->getOrderBy());
        }
        if ($buildGroup) {
            $this->buildGroup($query, $qs->getGroupBy());
            $this->buildHaving($query, $qs->getHaving());
        }
        if ($buildLimitOffset) {
            $this->buildLimitOffset($query, $qs->getLimit(), $qs->getOffset());
        }
        return $query;
    }

    public function all($sql = false)
    {
        $key = $this->getCacheKey('all');
        $query = $this->getQueryBuilder()->setCacheKey($key);
        if ($builtQuery = $this->getCachedQuery('all')) {
            if ($sql) {
                return $query->interpolateQuery($builtQuery[0], $builtQuery[1]);
            }
            return $query->get($builtQuery);
        }

        $qs = $this->getQuerySet();

        $select = $qs->getSelect();
        if (!$select) {
            $select = $this->defaultSelect();
        }
        $select = $this->buildSelect($select);
        if ($qs->getHasManyRelations()) {
            if (!$qs->getGroupBy() && $qs->getAutoGroup()) {
                $query->select($select);
                $query->groupBy($this->column($this->getTableName(), 'id'));
            } elseif ($qs->getAutoDistinct()) {
                $query->selectDistinct($select);
            }
        } else {
            $query->select($select);
        }
        $this->buildQuery($query);
        if ($sql) {
            return $query->getRawQuery();
        }
        $result = $query->get();
        return $result;
    }

    public function rawAll($query, $bindings = [])
    {
        return $this->getQueryBuilder()->rawAll($query, $bindings);
    }

    public function rawGet($query, $bindings = [])
    {
        return $this->getQueryBuilder()->rawGet($query, $bindings);
    }

    public function get($sql = false)
    {
        $query = $this->getQueryBuilder()->setCacheKey($this->getCacheKey('get'));
        if ($builtQuery = $this->getCachedQuery('get')) {
            if ($sql) {
                return $query->interpolateQuery($builtQuery[0], $builtQuery[1]);
            }
            return $query->first($builtQuery);
        }

        $qs = $this->getQuerySet();

        $this->buildQuery($query);

        $select = $qs->getSelect();
        if (!$select) {
            $select = $this->defaultSelect();
        }
        $select = $this->buildSelect($select);
        $query->select($select);

        if ($sql) {
            return $query->getRawQuery();
        }

        $result = $query->first();
        return $result;
    }

    public function defaultSelect()
    {
        $select = [];
        $select[] = $this->column($this->getTableName(), '*');
        foreach ($this->getQuerySet()->getWith() as $relationName) {
            $table = $this->getRelationTable($relationName);
            $relationModel = $this->getRelationModel($relationName);
            $attributes = $relationModel->getFieldsManager()->getDbAttributesList();
            foreach ($attributes as $attribute) {
                $select[$this->column($table, $attribute)] = $relationName . '__' . $attribute;
            }
        }
        return $select;
    }


    public function buildSelect($select)
    {
        $result = [];
        foreach ($select as $key => $value) {
            if ($value instanceof Expression) {
                $value = $this->convertExpression($value);
            }
            if ($value == '*') {
                $value = $this->column($this->getTableName(), '*');
            }
            $result[$key] = $value;
        }
        return $result;
    }

    public function aggregate(Aggregation $aggregation, $sql = false)
    {
        $aKey = 'aggregate' . $aggregation::getSql($aggregation->getField());
        $key = $this->getCacheKey($aKey);
        $query = $this->getQueryBuilder()->setCacheKey($key);
        if ($builtQuery = $this->getCachedQuery($aKey)) {
            if ($sql) {
                return $query->interpolateQuery($builtQuery[0], $builtQuery[1]);
            }
            $item = $query->first($builtQuery);
            if (isset($item['aggregation'])) {
                return $item['aggregation'];
            }
            return null;
        }

        $this->buildQuery($query, false);

        $field = $aggregation->getField();
        if (!$aggregation->getRaw()) {
            $field = $this->relationColumnAlias($field, true);
            $field = $this->sanitize($field);
        }
        $query->select(new Raw($aggregation->getSql($field) . ' as aggregation'));
        if ($sql) {
            return $query->getRawQuery();
        }
        $item = $query->first();
        if (isset($item['aggregation'])) {
            return $item['aggregation'];
        }
        return null;
    }

    public function update($data = [], $sql = false)
    {
        $query = $this->getQueryBuilder();
        $this->buildQuery($query, false, false);

        if ($this->getQuerySet()->hasRelations()) {
            $query = $this->wrapQuery($query);
        }

        $updateData = [];
        foreach ($data as $attribute => $value) {
            $column = $this->relationColumnAlias($attribute);
            if ($value instanceof Expression) {
                $value = $this->convertExpression($value);
            }
            $updateData[$column] = $value;
        }
        if ($sql) {
            return $query->getRawQuery('update', $updateData);
        }
        $pdoStatement = $query->update($updateData);
        return $pdoStatement->rowCount();
    }

    public function delete($sql = false)
    {
        $query = $this->getQueryBuilder();
        $this->buildQuery($query, false, false);

        if ($this->getQuerySet()->hasRelations()) {
            $query = $this->wrapQuery($query);
        }

        if ($sql) {
            return $query->getRawQuery('delete');
        }
        $pdoStatement = $query->delete();
        return $pdoStatement->rowCount();
    }

    public function values($columns = [], $distinct = true, $sql = false)
    {
        $key = $this->getCacheKey('values');
        $query = $this->getQueryBuilder()->setCacheKey($key);
        if ($builtQuery = $this->getCachedQuery('values')) {
            if ($sql) {
                return $query->interpolateQuery($builtQuery[0], $builtQuery[1]);
            }
            return $query->get($builtQuery);
        }

        $qs = $this->getQuerySet();

        if (!$columns) {
            $select = $this->column($this->getTableName(), '*');
        } else {
            $select = [];
            foreach ($columns as $attribute) {
                if ($attribute instanceof Expression) {
                    $select[] = $this->convertExpression($attribute);
                } else {
                    $column = $this->relationColumnAlias($attribute, true);
                    $column = $this->sanitize($column);
                    $attribute = $this->sanitize($attribute);
                    $select[] = $query->raw($column . ' as ' . $attribute);
                }

            }
        }

        if ($qs->getHasManyRelations() && $distinct) {
            $query->selectDistinct($select);
        } else {
            $query->select($select);
        }

        $this->buildQuery($query);
        if ($sql) {
            return $query->getRawQuery();
        }
        $result = $query->get();
        return $result;
    }

    /**
     * Wrap query for update/delete
     */
    public function wrapQuery($query)
    {
        // Create a temporary table for correct operation with JOIN
        $queryUpdate = $this->getQueryBuilder();
        $queryWrapper = $this->getQueryBuilderRaw();

        $pk = $this->relationColumnAlias('pk');
        $pkAttribute = $this->relationColumnAttribute('__this', 'pk');

        $tempTable = 'temp_table_wrapper';

        $query->select($pk);
        $queryWrapper
            ->from($query->subQuery($query, $this->sanitize($tempTable)))
            ->select($this->column($tempTable, $pkAttribute));
        $queryUpdate->where($pk, 'IN', $query->subQuery($queryWrapper));
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
     * @param $query QueryBuilder
     * @param $conditions
     * @param string $operator
     * @param bool $clear
     */
    public function buildConditions($query, $conditions, $operator = 'and', $clear = false)
    {
        $result = [];
        if ($clear) {
            $conditions = $this->clearConditions($conditions);
        }
        if (!is_array($conditions) || isset($conditions['relation'])) {
            $conditions = [$conditions];
        }
        if (isset($conditions[0]) && in_array($conditions[0], ['not', 'and', 'or'])) {
            $operator = array_shift($conditions);
        }
        foreach ($conditions as $key => $condition) {
            if (is_array($condition) && isset($condition['relation'])) {
                $lookupManager = $this->getQuerySet()->getLookupManager();
                $column = $this->columnAlias($condition['relation'], $condition['field']);
                $value = $condition['value'];
                if ($value instanceof Expression) {
                    $value = $this->convertExpression($value);
                }
                $result[] = $lookupManager->processCondition($query, $column, $condition['lookup'], $value, $operator);
            } elseif ($condition instanceof Expression) {
                $expression = $this->convertExpression($condition);
                $joiner = 'AND';
                if ($operator == 'or') {
                    $joiner = 'OR';
                } elseif ($operator == 'not') {
                    $joiner = 'AND NOT';
                }
                $result[] = $query->buildWhere($expression, null, null, $joiner);
            } elseif (is_array($condition)) {
                $joiner = 'AND';
                if ($operator == 'or') {
                    $joiner = 'OR';
                } elseif ($operator == 'not') {
                    $joiner = 'AND NOT';
                }
                $result[] = $query->buildWhere($this->buildConditions($query, $condition, 'and'), null, null, $joiner);
            }
        }
        return $result;
    }

    /**
     * @param $query QueryBuilder
     * @param $order array
     * @return array
     */
    public function buildOrder($query, $order)
    {
        foreach ($order as $item) {
            if ($item instanceof Expression) {
                $value = $this->convertExpression($item);
                $query->orderBy($value);
            } elseif (is_array($item)) {
                $column = $this->columnAlias($item['relation'], $item['field']);

                if ($this->getQuerySet()->getHasManyRelations()) {
                    $alias = implode('__', ['order', $item['relation'], $item['field']]);
                    $query->select([$column => $alias]);
                    $query->orderBy($alias, $item['direction']);
                } else {
                    $query->orderBy($column, $item['direction']);
                }

            }
        }
    }

    /**
     * @param $query QueryBuilder
     * @param $group array
     * @return array
     */
    public function buildGroup($query, $group)
    {
        foreach ($group as $item) {
            if ($item instanceof Expression) {
                $value = $this->convertExpression($item);
                $query->groupBy($value);
            } elseif (is_array($item)) {
                $query->groupBy($this->columnAlias($item['relation'], $item['field']));
            }
        }
    }

    /**
     * @param $query QueryBuilder
     * @param $having Expression
     * @return array
     */
    public function buildHaving($query, $having)
    {
        if ($having instanceof Expression) {
            $value = $this->convertExpression($having);
            $query->having($value);
        } elseif ($having instanceof Having) {
            $aggregation = $having->getAggregation();
            $field = $aggregation->getField();
            $name = 'hav';
            if (!$aggregation->getRaw()) {
                $field = $this->relationColumnAlias($field, true);
                $field = $this->sanitize($field);
            }
            $query->select(new Raw($aggregation->getSql($field) . ' as ' . $name));
            $query->having(new Raw($name . ' ' . $having->getCondition()));
            if (!$query->getStatement('groupBys')) {
                $query->groupBy($this->columnAlias(null, $this->getModel()->getPkAttribute()));
            }
        }
    }

    /**
     * @param $query QueryBuilder
     * @param $limit int|null
     * @param $offset int|null
     * @return array
     * @internal param array $order
     */
    public function buildLimitOffset($query, $limit, $offset)
    {
        if (isset($limit)) {
            $query->limit($limit);
        }

        if (isset($offset)) {
            $query->offset($offset);
        }
    }

    public function convertExpression(Expression $expression)
    {
        $value = $expression->getExpression();
        if ($expression->getUseAliases() && ($aliases = $expression->getAliases())) {
            $replaces = [];
            foreach ($aliases as $relationColumn) {
                $column = $this->relationColumnAlias($relationColumn, true);
                $replaces['{' . $relationColumn . '}'] = $this->sanitize($column);
            }
            $value = strtr($value, $replaces);
        }
        return new Raw($value, $expression->getParams());
    }

    public function getCacheKey($type)
    {
        return $this->_key . '#' . $type;
    }

    public function getCachedQuery($type)
    {
        $key = $this->getCacheKey($type);
        $cacheTimeout = Phact::app()->db->getCacheQueriesTimeout();
        return is_null($cacheTimeout) ? null : Phact::app()->cache->get($key);
    }
}