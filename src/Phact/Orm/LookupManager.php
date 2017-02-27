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
use Pixie\QueryBuilder\QueryBuilderHandler;

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
            $this->{$method}($query, $column, $value, $operator);
        } else {
            throw new InvalidArgumentException("Unknown lookup '$lookup'");
        }
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processExact($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, '=', $value);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processContains($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, 'LIKE', '%' . $value . '%');
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processIn($query, $column, $value, $operator)
    {
        $method = 'whereIn';
        if ($operator == 'or') {
            $method = 'orWhereIn';
        }
        $query->{$method}($column, $value);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processGt($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, '>', $value);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processGte($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, '>=', $value);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processLt($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, '<', $value);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processLte($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, '<=', $value);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processStartswith($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, 'LIKE', '%' . $value);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processEndswith($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, 'LIKE', $value . '%');
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processRange($query, $column, $value, $operator)
    {
        $method = 'whereBetween';
        if ($operator == 'or') {
            $method = 'orWhereBetween';
        }
        $query->{$method}($column, $value[0], $value[1]);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processIsnull($query, $column, $value, $operator)
    {
        $method = 'whereNull';
        if ($operator == 'and') {
            if ($value) {
                $method = 'whereNull';
            } else {
                $method = 'orWhereNull';
            }
        } else {
            if ($value) {
                $method = 'whereNotNull';
            } else {
                $method = 'orWhereNotNull';
            }
        }
        $query->{$method}($column);
    }

    /**
     * @param $query QueryBuilderHandler
     * @param $column string
     * @param $value mixed
     * @param $operator string "or"|"and"
     */
    public function processRegex($query, $column, $value, $operator)
    {
        $method = 'where';
        if ($operator == 'or') {
            $method = 'orWhere';
        }
        $query->{$method}($column, 'REGEXP', $value);
    }
}