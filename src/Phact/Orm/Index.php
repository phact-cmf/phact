<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 08/02/2020 11:39
 */

namespace Phact\Orm;


class Index
{
    private $indexName;
    /**
     * @var array
     */
    private $columns;
    /**
     * @var bool
     */
    private $isUnique;
    /**
     * @var bool
     */
    private $isPrimary;
    /**
     * @var array
     */
    private $flags;
    /**
     * @var array
     */
    private $options;

    public function __construct($indexName, array $columns, $isUnique = false, $isPrimary = false, array $flags = [], array $options = [])
    {
        $this->indexName = $indexName;
        $this->columns = $columns;
        $this->isUnique = $isUnique;
        $this->isPrimary = $isPrimary;
        $this->flags = $flags;
        $this->options = $options;
    }

    /**
     * @return mixed
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * @return array
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}