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
 * @date 15/04/16 15:52
 */

namespace Phact\Orm;

use InvalidArgumentException;

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
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processExact($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, '=', $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processContains($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, 'LIKE', '%' . $value . '%', $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processIn($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, 'IN', $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processGt($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, '>', $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processGte($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, '>=', $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processLt($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, '<', $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processLte($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, '<=', $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processStartswith($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, 'LIKE', '%' . $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processEndswith($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, 'LIKE', $value . '%', $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processRange($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, 'BETWEEN', $value, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processIsnull($query, $column, $value, $operator)
    {
        $prefix = 'NOT';
        if ($value) {
            $prefix = 'NOT';
        }
        $key = $query->getAdapter()->wrapSanitizer($query->addTablePrefix($column));
        return $query->buildWhere($query->raw("{$key} IS {$prefix} NULL"), null, null, $operator);
    }

    /**
     * @param $query QueryBuilder
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processRegex($query, $column, $value, $operator)
    {
        return $query->buildWhere($column, 'REGEXP', $value, $operator);
    }
}