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
 * @date 14/04/16 08:24
 */

namespace Phact\Orm;

use Exception;
use InvalidArgumentException;
use Phact\Helpers\SmartProperties;
use Phact\Main\Phact;
use Phact\Orm\Aggregations\Aggregation;
use Pixie\QueryBuilder\QueryBuilderHandler;
use Pixie\QueryBuilder\Raw;

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

    protected $_query;

    /**
     * @var \Phact\Orm\QuerySet
     */
    protected $_querySet;

    protected $_aliases;

    public function __construct($querySet)
    {
        $this->_querySet = $querySet;
        $this->setAliases();
    }

    /**
     * @return \Phact\Orm\QuerySet
     */
    public function getQuerySet()
    {
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
        return $this->querySet->model;
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
        return $qb->table([$this->getTableName()])->setFetchMode(\PDO::FETCH_ASSOC);
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
        $relations = $this->querySet->getRelations();
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
        $field = $model->getField($attribute);
        if ($field) {
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
        $attribute = $this->relationColumnAttribute($relationName, $attribute);
        $tableName = $this->getTableOrAlias($relationName, $tableName ?: $this->getRelationTable($relationName));
        return $this->column($tableName, $attribute, $addTablePrefix);
    }

    public function column($tableName, $attribute, $addTablePrefix = false)
    {
        if ($addTablePrefix) {
            $tableName = $this->getQueryBuilderRaw()->addTablePrefix($tableName);
        }
        return $tableName . '.' . $attribute;
    }

    /**
     * @param $query \Pixie\QueryBuilder\QueryBuilderHandler
     * @return \Pixie\QueryBuilder\QueryBuilderHandler
     * @throws Exception
     */
    public function processJoins($query)
    {
        $relations = $this->getQuerySet()->getRelations();
        foreach ($relations as $relationName => $relation) {
            // A relation and a table on which a join is to be build
            $currentRelationName = $this->getQuerySet()->parentRelationName($relationName);
            $currentTable = $this->getRelationTable($currentRelationName);

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
                                $this->columnAlias($currentRelationName, $attributeFrom, $currentTable),
                                '=',
                                $this->column($alias ?: $tableName, $attributeTo),
                                isset($join['type']) ? $join['type'] : 'inner'
                            );

                            // We change the current join
                            $currentTable = $tableName;
                            $currentRelationName = $relationName;
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
            $this->buildConditions($query, $qs->getWhere(), 'and', true);
        }
        if ($buildOrder) {
            $this->buildOrder($query, $qs->getOrderBy());
        }
        if ($buildGroup) {
            $this->buildGroup($query, $qs->getGroupBy());
        }
        if ($buildLimitOffset) {
            $this->buildLimitOffset($query, $qs->getLimit(), $qs->getOffset());
        }
        return $query;
    }

    public function all($sql = false)
    {
        $query = $this->getQueryBuilder();
        $qs = $this->getQuerySet();

        $select = $this->column($this->getTableName(), '*');
        if ($qs->getHasManyRelations()) {
            $query->selectDistinct($select);
        } else {
            $query->select($select);
        }
        $this->buildQuery($query);
        if ($sql) {
            return $query->getQuery()->getRawSql();
        }
        $result = $query->get();
        return $result;
    }

    public function get($sql = false)
    {
        $query = $this->getQueryBuilder();
        $this->buildQuery($query);
        $query->select($this->column($this->getTableName(), '*'));

        if ($sql) {
            return $query->getQuery()->getRawSql();
        }

        $result = $query->first();
        return $result;
    }

    public function aggregate(Aggregation $aggregation, $sql = false)
    {
        $query = $this->getQueryBuilder();
        $this->buildQuery($query, false);

        $field = $aggregation->getField();
        if (!$aggregation->getRaw()) {
            $field = $this->relationColumnAlias($field, true);
            $field = $this->sanitize($field);
        }
        $query->select(new Raw($aggregation->getSql($field) . ' as aggregation'));
        if ($sql) {
            return $query->getQuery()->getRawSql();
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
            $updateData[$column] = $value;
        }
        if ($sql) {
            return $query->getQuery('update', $updateData)->getRawSql();
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
            return $query->getQuery('delete')->getRawSql();
        }
        $pdoStatement = $query->delete();
        return $pdoStatement->rowCount();
    }

    public function values($columns = [], $distinct = true, $sql = false)
    {
        $query = $this->getQueryBuilder();
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
            return $query->getQuery()->getRawSql();
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
        $queryUpdate->whereIn($pk, $query->subQuery($queryWrapper));
        return $queryUpdate;
    }

    public function clearConditions($conditions)
    {
        if (is_array($conditions) && count($conditions) == 1) {
            $conditions = array_shift($conditions);
            return $this->clearConditions($conditions);
        } else {
            return $conditions;
        }
    }

    /**
     * @param $query \Pixie\QueryBuilder\QueryBuilderHandler
     * @param $conditions
     * @param string $operator
     * @param bool $clear
     */
    public function buildConditions($query, $conditions, $operator = 'and', $clear = false)
    {
        if ($clear) {
            $conditions = $this->clearConditions($conditions);
        }
        if (!is_array($conditions) || isset($conditions['relation'])) {
            $conditions = [$conditions];
        }
        foreach ($conditions as $key => $condition) {
            if (is_array($condition) && isset($condition['relation'])) {
                $lookupManager = $this->getQuerySet()->getLookupManager();
                $column = $this->columnAlias($condition['relation'], $condition['field']);
                $value = $condition['value'];
                if ($value instanceof Expression) {
                    $value = $this->convertExpression($value);
                }
                $lookupManager->processCondition($query, $column, $condition['lookup'], $value, $operator);
            } elseif ($condition instanceof Expression) {
                $expression = $this->convertExpression($condition);
                $method = 'where';
                if ($operator == 'or') {
                    $method = 'orWhere';
                }
                $query->{$method}($expression);
            } elseif (is_array($condition)) {
                $nextOperator = 'and';
                if (isset($condition[0]) && in_array($condition[0], ['not', 'and', 'or'])) {
                    $nextOperator = array_shift($condition);
                }
                $method = 'where';
                if ($nextOperator == 'not') {
                    $nextOperator = 'and';
                    if ($operator == 'and') {
                        $method = 'whereNot';
                    } else {
                        $method = 'orWhereNot';
                    }
                } else {
                    if ($operator == 'and') {
                        $method = 'where';
                    } else {
                        $method = 'orWhere';
                    }
                }
                $query->{$method}(function($q) use ($condition, $nextOperator) {
                    $this->buildConditions($q, $condition, $nextOperator);
                });
            }
        }
    }

    /**
     * @param $query \Pixie\QueryBuilder\QueryBuilderHandler
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
                $query->orderBy($column, $item['direction']);
            }
        }
    }

    /**
     * @param $query \Pixie\QueryBuilder\QueryBuilderHandler
     * @param $group array
     * @return array
     */
    public function buildGroup($query, $group)
    {
        foreach ($group as $item) {
            if ($item instanceof Expression) {
                $value = $this->convertExpression($item);
                $query->groupBy($value);
            } elseif (is_string($item)) {
                $query->groupBy($item);
            }
        }
    }

    /**
     * @param $query \Pixie\QueryBuilder\QueryBuilderHandler
     * @param $limit int|null
     * @param $offset int|null
     * @return array
     * @internal param array $order
     */
    public function buildLimitOffset($query, $limit, $offset)
    {
        if (!is_null($limit)) {
            $query->limit($limit);
        }

        if (!is_null($offset)) {
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
}