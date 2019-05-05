<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/05/2019 11:20
 */

namespace Phact\Orm;


interface QuerySetInterface
{
    public function all();

    public function get();

    public function count();

    public function sum($attribute);

    public function max($attribute);

    public function avg($attribute);

    public function min($attribute);

    public function filter($filter = []);

    public function exclude($exclude = []);

    public function order($order = []);

    public function having($expression);

    public function group($group = []);

    public function limit($limit);

    public function offset($offset);

    public function with($with = []);

    public function select($select = []);

    public function values($columns = [], $flat = false, $distinct = true);

    public function choices($key, $value);

    public function raw($query, $params = []);

    public function rawAll($query, $params = []);

    public function rawGet($query, $params = []);

    public function update($data = []);

    public function delete();

    public function allSql();

    public function getSql();

    public function valuesSql($columns = [], $flat = false, $distinct = true);

    public function updateSql($data = []);

    public function deleteSql();

    public function countSql();

    public function maxSql($attribute);

    public function minSql($attribute);

    public function avgSql($attribute);

    public function sumSql($attribute);
}