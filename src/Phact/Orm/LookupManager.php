<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 15/04/16 15:52
 */

namespace Phact\Orm;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use InvalidArgumentException;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Phact\Orm\Adapters\PostgresqlAdapter;

class LookupManager
{
    public static $defaultLookup = 'exact';

    public static function map()
    {
        return [
            'exact',
            'contains',
            'in',
            'gt',
            'gte',
            'lt',
            'lte',
            'startswith',
            'endswith',
            'range',
            'isnull',
            'regex'
        ];
    }

    public function processCondition($query, $column, $lookup, $value, $operator)
    {
        if (in_array($lookup, static::map())) {
            $method = 'process' . ucfirst($lookup);
            return $this->{$method}($query, $column, $value, strtoupper($operator));
        } else {
            throw new InvalidArgumentException("Unknown lookup '$lookup'");
        }
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processExact($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, '=', $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processContains($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, 'LIKE', '%' . $value . '%', $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processIn($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, 'IN', $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processGt($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, '>', $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processGte($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, '>=', $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processLt($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, '<', $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processLte($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, '<=', $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processStartswith($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, 'LIKE', $value . '%', $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processEndswith($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, 'LIKE','%' . $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processRange($query, $column, $value, $operator)
    {
        return QueryLayer::buildWhere($column, 'BETWEEN', $value, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     */
    public function processIsnull($query, $column, $value, $operator)
    {
        $prefix = ' NOT';
        if ($value) {
            $prefix = '';
        }
        return QueryLayer::buildWhere("{$column} IS{$prefix} NULL", null, null, $operator);
    }

    /**
     * @param $query DBALQueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function processRegex($query, $column, $value, $operator)
    {
        $platform = $query->getConnection()->getDatabasePlatform();
        $expression = $platform->getRegexpExpression();
        if ($platform instanceof PostgreSqlPlatform) {
            $expression = PostgresqlAdapter::getRegexpExpression();
        }
        return QueryLayer::buildWhere($column, $expression, $value, $operator);
    }
}