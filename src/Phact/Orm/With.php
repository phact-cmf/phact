<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 04/05/2019 13:33
 */

namespace Phact\Orm;


class With
{
    public const MODE_SELECT = 10;

    public const MODE_PREFETCH = 20;

    private $relationName;

    private $with = [];

    private $values = [];

    private $namedSelection;

    private int $mode = self::MODE_SELECT;

    public function __construct(string $relationName)
    {
        $this->relationName = $relationName;
    }

    /**
     * @return string
     */
    public function getRelationName(): string
    {
        return $this->relationName;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        $key = $this->relationName;
        if ($this->namedSelection) {
            $key .= '->' . $this->namedSelection;
        }
        return $key;
    }

    /**
     * @param mixed $namedSelection
     * @return With
     */
    public function setNamedSelection($namedSelection)
    {
        $this->namedSelection = $namedSelection;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNamedSelection()
    {
        return $this->namedSelection;
    }

    /**
     * @param array $with
     * @return With
     */
    public function setWith(array $with): With
    {
        $this->with = $with;
        return $this;
    }

    /**
     * @return array
     */
    public function getWith(): array
    {
        return $this->with;
    }

    /**
     * @param array $values
     * @return With
     */
    public function setValues(array $values): With
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return int
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     * @return With
     */
    public function setMode(int $mode): With
    {
        $this->mode = $mode;
        return $this;
    }

    public function prefetch(): With
    {
        return $this->setMode(self::MODE_PREFETCH);
    }

    public function isPrefetch(): bool
    {
        return $this->mode === self::MODE_PREFETCH;
    }

    public function isSelect(): bool
    {
        return $this->mode === self::MODE_SELECT;
    }
}