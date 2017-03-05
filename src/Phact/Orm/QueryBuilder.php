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
 * @date 05/03/17 11:42
 */

namespace Phact\Orm;


use Exception;
use PDO;
use Phact\Main\Phact;

class QueryBuilder
{
    /**
     *
     * @var Connection
     */
    protected $_connection;
    /**
     * @var array
     */
    protected $_statements = array();

    protected $_fetchParameters = array(\PDO::FETCH_ASSOC);

    protected $_tablePrefix = null;

    protected $_pdoStatement = null;

    protected $_cacheKey = null;

    public function __construct($connection = null, $tablePrefix = null)
    {
        $this->_connection = $connection;
        $this->_tablePrefix = $tablePrefix;
    }

    public function setFetchParameters($parameters)
    {
        $this->_fetchParameters = $parameters;
    }

    public function getAdapter()
    {
        return $this->_connection->getAdapter();
    }

    public function setCacheKey($cacheKey)
    {
        $this->_cacheKey = $cacheKey;
        return $this;
    }

    public function table($tables)
    {
        if (!is_array($tables)) {
            // because a single table is converted to an array anyways,
            // this makes sense.
            $tables = func_get_args();
        }

        $tables = $this->addTablePrefix($tables, false);
        $this->addStatement('tables', $tables);
        return $this;
    }

    public function addTablePrefix($values, $tableFieldMix = true)
    {
        if (is_null($this->_tablePrefix)) {
            return $values;
        }
        //@TODO
    }

    public function addStatement($key, $value)
    {
        if (!is_array($value)) {
            $value = array($value);
        }

        if (!array_key_exists($key, $this->_statements)) {
            $this->_statements[$key] = $value;
        } else {
            $this->_statements[$key] = array_merge($this->_statements[$key], $value);
        }
    }

    public function getPdo()
    {
        return $this->_connection->getPdo();
    }

    public function queryStatement($type = 'select', $dataToBePassed = array())
    {
        list($sql, $bindings) = $this->getQuery($type, $dataToBePassed);
        return $this->statement($sql, $bindings);
    }

    public function statement($sql, $bindings = array())
    {
        $start = microtime(true);
        $pdoStatement = $this->getPdo()->prepare($sql);
        foreach ($bindings as $key => $value) {
            $pdoStatement->bindValue(
                is_int($key) ? $key + 1 : $key,
                $value,
                is_int($value) || is_bool($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
        $pdoStatement->execute();
        return array($pdoStatement, microtime(true) - $start);
    }

    public function getRawQuery($type = 'select', $dataToBePassed = array())
    {
        list($sql, $bindings) = $this->getQuery($type, $dataToBePassed);
        return $this->interpolateQuery($sql, $bindings);
    }

    public function getQuery($type = 'select', $dataToBePassed = array())
    {
        $allowedTypes = array('select', 'insert', 'insertignore', 'replace', 'delete', 'update', 'criteriaonly');
        if (!in_array(strtolower($type), $allowedTypes)) {
            throw new Exception($type . ' is not a known type.', 2);
        }
        $queryArr = $this->getAdapter()->$type($this->_statements, $dataToBePassed);
        return [$queryArr['sql'], $queryArr['bindings']];
    }

    public function subQuery(QueryBuilder $queryBuilder, $alias = null)
    {
        $sql = '(' . $queryBuilder->getRawQuery() . ')';
        if ($alias) {
            $sql = $sql . ' as ' . $alias;
        }
        return $queryBuilder->raw($sql);
    }

    public function interpolateQuery($query, $params)
    {
        $keys = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value)) {
                $values[$key] = $this->getPdo()->quote($value);
            }

            if (is_array($value)) {
                $values[$key] = implode(',', $this->getPdo()->quote($value));
            }

            if (is_null($value)) {
                $values[$key] = 'NULL';
            }
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }

    /**
     * Get all rows
     *
     * @param null $builtQuery
     * @return null|\stdClass
     * @throws Exception
     */
    public function get($builtQuery = null)
    {
        $executionTime = 0;
        if (is_null($this->_pdoStatement)) {
            if ($builtQuery) {
                list($sql, $bindings) = $builtQuery;
            } else {
                $query = $this->getQuery('select');
                $this->cacheQuery($query);
                list($sql, $bindings) = $query;
            }
            list($this->_pdoStatement, $executionTime) = $this->statement($sql, $bindings);

        }
        $start = microtime(true);
        $result = call_user_func_array(array($this->_pdoStatement, 'fetchAll'), $this->_fetchParameters);
        $executionTime += microtime(true) - $start;
        $this->_pdoStatement = null;
        return $result;
    }

    /**
     * Get first row
     *
     * @param null $rawQuery
     * @param null $cacheKey
     * @return null|\stdClass
     */
    public function first($rawQuery = null)
    {
        $this->limit(1);
        $result = $this->get($rawQuery);
        return empty($result) ? null : $result[0];
    }

    public function count()
    {
        // Get the current statements
        $originalStatements = $this->_statements;

        unset($this->_statements['orderBys']);
        unset($this->_statements['limit']);
        unset($this->_statements['offset']);

        $count = $this->aggregate('count');
        $this->statements = $originalStatements;

        return $count;
    }

    /**
     * @param $type
     *
     * @return int
     */
    public function aggregate($type)
    {
        // Get the current selects
        $mainSelects = isset($this->_statements['selects']) ? $this->_statements['selects'] : null;
        // Replace select with a scalar value like `count`
        $this->_statements['selects'] = array($this->raw($type . '(*) as field'));
        $row = $this->get();

        // Set the select as it was
        if ($mainSelects) {
            $this->_statements['selects'] = $mainSelects;
        } else {
            unset($this->_statements['selects']);
        }

        if (is_array($row[0])) {
            return (int) $row[0]['field'];
        } elseif (is_object($row[0])) {
            return (int) $row[0]->field;
        }

        return 0;
    }

    public function raw($value, $bindings = [])
    {
        return new Raw($value, $bindings);
    }

    public function insert($data, $type = 'insert')
    {
        if (!is_array(current($data))) {
            list($result, $executionTime) = $this->queryStatement($type, $data);
            $return = $result->rowCount() === 1 ? $this->getPdo()->lastInsertId() : null;
        } else {
            // Its a batch insert
            $return = array();
            $executionTime = 0;
            foreach ($data as $subData) {
                list($result, $time) = $this->queryStatement($type, $subData);
                $executionTime += $time;
                if ($result->rowCount() === 1) {
                    $return[] = $this->getPdo()->lastInsertId();
                }
            }
        }
        return $return;
    }

    public function update($data)
    {
        list($response, $executionTime) = $this->queryStatement('update', $data);
        return $response;
    }

    public function delete()
    {
        list($response, $executionTime) = $this->queryStatement('delete');
        return $response;
    }

    public function from($tables)
    {
        if (!is_array($tables)) {
            $tables = func_get_args();
        }

        $tables = $this->addTablePrefix($tables, false);
        $this->addStatement('tables', $tables);
        return $this;
    }

    public function select($fields)
    {
        if (!is_array($fields)) {
            $fields = func_get_args();
        }
        $fields = $this->addTablePrefix($fields);
        $this->addStatement('selects', $fields);
        return $this;
    }

    public function selectDistinct($fields)
    {
        $this->select($fields);
        $this->addStatement('distinct', true);
        return $this;
    }

    public function groupBy($field)
    {
        $field = $this->addTablePrefix($field);
        $this->addStatement('groupBys', $field);
        return $this;
    }

    public function orderBy($fields, $defaultDirection = 'ASC')
    {
        if (!is_array($fields)) {
            $fields = array($fields);
        }

        foreach ($fields as $key => $value) {
            $field = $key;
            $type = $value;
            $bindings = [];
            if (is_int($key)) {
                $field = $value;
                $type = $defaultDirection;
            }
            if (!$field instanceof Raw) {
                $field = $this->addTablePrefix($field);
            } else {
                $bindings = $field->getBindings();
            }
            $this->_statements['orderBys'][] = compact('field', 'type', 'bindings');
        }

        return $this;
    }

    /**
     * @param $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->_statements['limit'] = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->_statements['offset'] = $offset;
        return $this;
    }

    public function having($key, $operator, $value, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->_statements['havings'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }
    
    public function where($key, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereHandler($key, $operator, $value);
    }

    /**
    public function orWhere($key, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->whereHandler($key, $operator, $value, 'OR');
    }
    
    public function whereNot($key, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereHandler($key, $operator, $value, 'AND NOT');
    }
    
    public function orWhereNot($key, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereHandler($key, $operator, $value, 'OR NOT');
    }
    
    public function whereIn($key, $values)
    {
        return $this->whereHandler($key, 'IN', $values, 'AND');
    }
    
    public function whereNotIn($key, $values)
    {
        return $this->whereHandler($key, 'NOT IN', $values, 'AND');
    }
    
    public function orWhereIn($key, $values)
    {
        return $this->whereHandler($key, 'IN', $values, 'OR');
    }

    public function orWhereNotIn($key, $values)
    {
        return $this->whereHandler($key, 'NOT IN', $values, 'OR');
    }
    
    public function whereBetween($key, $valueFrom, $valueTo)
    {
        return $this->whereHandler($key, 'BETWEEN', array($valueFrom, $valueTo), 'AND');
    }
    
    public function orWhereBetween($key, $valueFrom, $valueTo)
    {
        return $this->whereHandler($key, 'BETWEEN', array($valueFrom, $valueTo), 'OR');
    }
    
    public function whereNull($key)
    {
        return $this->whereNullHandler($key);
    }
    
    public function whereNotNull($key)
    {
        return $this->whereNullHandler($key, 'NOT');
    }
    
    public function orWhereNull($key)
    {
        return $this->whereNullHandler($key, '', 'or');
    }
    
    public function orWhereNotNull($key)
    {
        return $this->whereNullHandler($key, 'NOT', 'or');
    }

    protected function whereNullHandler($key, $prefix = '', $operator = '')
    {
        $key = $this->getAdapter()->wrapSanitizer($this->addTablePrefix($key));
        return $this->{$operator . 'Where'}($this->raw("{$key} IS {$prefix} NULL"));
    }
    */

    public function whereHandler($key, $operator = null, $value = null, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->_statements['wheres'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }
    
    public function buildWhere($key, $operator = null, $value = null, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        return compact('key', 'operator', 'value', 'joiner');
    }

    public function setWhere($wheres = [])
    {
        $this->_statements['wheres'] = $wheres;
        return $this;
    }

    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        $table = $this->addTablePrefix($table, false);
        $this->_statements['joins'][] = compact('type', 'table', 'key', 'operator', 'value');
        return $this;
    }

    public function leftJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'left');
    }

    public function rightJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'right');
    }

    public function innerJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'inner');
    }

    public function setCacheQuery($key, $query)
    {
        $cacheTimeout = Phact::app()->db->getCacheQueriesTimeout();
        if (!is_null($cacheTimeout)) {
            Phact::app()->cache->set($key, $query, $cacheTimeout);
        }
    }

    public function cacheQuery($query)
    {
        if ($this->_cacheKey) {
            $this->setCacheQuery($this->_cacheKey, $query);
        }
    }
}