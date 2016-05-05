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

    public function getQueryBuilder()
    {
        return $this->getQuery()->getQueryBuilder();
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
        return $model->getTableName();
    }

    public function relationColumnAlias($column)
    {
        list($relationName, $attribute) = $this->getQuerySet()->getRelationColumn($column);
        return $this->columnAlias($relationName, $attribute);
    }

    public function columnAlias($relationName, $attribute, $tableName = null)
    {
        $tableName = $this->getTableOrAlias($relationName, $tableName ?: $this->getRelationTable($relationName));
        return $this->column($tableName, $attribute);
    }

    public function column($tableName, $attribute)
    {
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

    public function all($sql = false)
    {
        $qb = $this->getQueryBuilder();
        $query = $qb->table([$this->getTableName()])->setFetchMode(\PDO::FETCH_ASSOC);
        $qs = $this->getQuerySet();

        $select = $this->column($this->getTableName(), '*');
        if ($qs->getHasManyRelations()) {
            $query->selectDistinct($select);
        } else {
            $query->select($select);
        }

        $query = $this->processJoins($query);
        $this->buildConditions($query, $qs->getWhere(), 'and', true);
        $this->buildOrder($query, $qs->getOrderBy());
        if ($sql) {
            return $query->getQuery()->getRawSql();
        }
        $result = $query->get();
        return $result;
    }

    public function get($sql = false)
    {
        $qb = $this->getQueryBuilder();
        $query = $qb->table([$this->getTableName()])->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $query->first();
        return $result;
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
        $builtOrder = [];
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

    public function convertExpression(Expression $expression)
    {
        $value = $expression->getExpression();
        if ($expression->getUseAliases() && ($aliases = $expression->getAliases())) {
            $replaces = [];
            foreach ($aliases as $relationColumn) {
                $column = $this->relationColumnAlias($relationColumn);
                $replaces['{' . $relationColumn . '}'] = $this->sanitize($column);
            }
            $value = strtr($value, $replaces);
        }
        return new Raw($value, $expression->getParams());
    }
}