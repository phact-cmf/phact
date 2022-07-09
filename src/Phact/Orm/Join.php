<?php declare(strict_types=1);

namespace Phact\Orm;

class Join
{

    public const INNER = 'inner';
    public const LEFT = 'left';
    public const RIGHT = 'right';

    protected ?string $table = null;

    protected ?string $alias = null;

    protected ?QuerySetInterface $querySet = null;

    protected ?string $from;

    protected ?string $to;

    protected string $type = self::LEFT;

    /**
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * @param string|null $table
     * @return Join
     */
    public function setTable(?string $table): Join
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return QuerySetInterface|null
     */
    public function getQuerySet(): ?QuerySetInterface
    {
        return $this->querySet;
    }

    /**
     * @param QuerySetInterface $querySet
     * @return Join
     */
    public function setQuerySet(QuerySetInterface $querySet): Join
    {
        $this->querySet = $querySet;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string|null $from
     * @return Join
     */
    public function setFrom(?string $from): Join
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @param string|null $to
     * @return Join
     */
    public function setTo(?string $to): Join
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Join
     */
    public function setType(string $type): Join
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string|null $alias
     * @return Join
     */
    public function setAlias(?string $alias): Join
    {
        $this->alias = $alias;
        return $this;
    }
}
