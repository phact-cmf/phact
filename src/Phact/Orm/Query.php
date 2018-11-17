<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 15/04/16 11:02
 */

namespace Phact\Orm;

use Doctrine\DBAL\ParameterType;
use Phact\Main\Phact;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;

class Query
{
    protected $_connectionName = 'default';

    public function __construct($connectionName = null)
    {
        if($connectionName){
            $this->_connectionName = $connectionName;
        }
    }
    
    public function setConnectionName($connectionName)
    {
        $this->_connectionName = $connectionName;
    }

    public function getConnectionName()
    {
        return $this->_connectionName;
    }

    public function getConnection()
    {
        $connectionName = $this->getConnectionName();
        return Phact::app()->db->getConnection($connectionName);
    }

    public function getQueryBuilder()
    {
        return $this->getConnection()->createQueryBuilder();
    }

    /**
     * Quote columns names
     * @param $data array column-value array
     */
    public function quoteData($data)
    {
        $quotedData = [];
        foreach ($data as $column => $value) {
            $quotedData[$this->getConnection()->quoteIdentifier($column)] = $value;
        }
        return $quotedData;
    }

    public function insert($tableName, $data)
    {
        $this->getConnection()->insert($tableName, $this->quoteData($data));
        return $this->getConnection()->lastInsertId();
    }

    public function update($tableName, $conditions, $data)
    {
        return $this->getConnection()->update($tableName, $this->quoteData($data), $conditions);
    }

    public function delete($tableName, $conditions)
    {
        return $this->getConnection()->delete($tableName, $conditions);
    }

    /**
     * @param $queryBuilder DBALQueryBuilder
     * @return string
     */
    public function getSQL($queryBuilder)
    {
        $query = $queryBuilder->getSQL();
        $params = $queryBuilder->getParameters();

        $keys = [];
        $values = $params;

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }

            if (is_string($value)) {
                $values[$key] = $this->getConnection()->quote($value);
            }

            if (is_null($value)) {
                $values[$key] = 'NULL';
            }
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }

    /**
     * @param $srcQueryBuilder DBALQueryBuilder
     * @param $dstQueryBuilder DBALQueryBuilder
     */
    public function prepareSubQuery($srcQueryBuilder, $dstQueryBuilder)
    {
        $query = $srcQueryBuilder->getSQL();
        $params = $srcQueryBuilder->getParameters();
        if (!$params) {
            return $query;
        }
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:(' . $key . ')/';
            } else {
                $keys[] = '/[?]/';
            }
        }
        $count = 0;
        $query = preg_replace_callback($keys, function ($match) use (&$count, $dstQueryBuilder, $params) {
            if (isset($match[1])) {
                $key = $match[1];
            } else {
                $key = $count;
            }
            if (!isset($params[$key])) {
                return $match[0];
            }
            $value = $params[$key];
            $placeholder = $dstQueryBuilder->createNamedParameter($params[$key], is_int($value) ? ParameterType::INTEGER : ParameterType::STRING);
            $count++;
            return $placeholder;
        }, $query, 1);
        return $query;
    }
}