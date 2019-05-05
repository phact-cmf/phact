<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 15.08.16
 * Time: 15:04
 */

namespace Phact\Orm;

use InvalidArgumentException;

class ManyToManyManager extends RelationManager implements RelationBatchInterface
{
    use FetchPreselectedWithTrait;

    /** @var Model field owner */
    public $ownerModel;

    /**
     * @var
     */
    public $backField;

    /**
     * @var string
     */
    public $backThroughName;

    /**
     * @var string
     */
    public $backThroughField;

    /**
     * @var string
     */
    public $through;

    /**
     * @var string
     */
    public $throughTable;

    /**
     * @var string
     */
    public $throughFromField;

    /**
     * @var string
     */
    public $throughToField;

    /**
     * @var string
     */
    public $toField;

    /**
     * @var string
     */
    public $fromField;

    /**
     * @var string
     */
    public $fieldName;

    public function nextManager(QuerySet $querySet): Manager
    {
        /** @var self $next */
        $next = parent::nextManager($querySet);
        $next->ownerModel = $this->ownerModel;
        $next->backField = $this->backField;
        $next->backThroughName = $this->backThroughName;
        $next->backThroughField = $this->backThroughField;
        $next->through = $this->through;
        $next->throughTable = $this->throughTable;
        $next->throughFromField = $this->throughFromField;
        $next->throughToField = $this->throughToField;
        $next->toField = $this->toField;
        $next->fromField = $this->fromField;
        $next->fieldName = $this->fieldName;
        return $next;
    }

    public function createQuerySet()
    {
        $qs = parent::createQuerySet();

        if ($this->backThroughName && $this->backThroughField) {
            $qs->filter([
                $this->backThroughName . '__' . $this->backThroughField => $this->getKey()
            ]);
        } else {
            $relationName = $this->getRelationName();
            $qs->appendRelation($relationName, null, [[
                'table' => $this->throughTable,
                'from' => $this->toField,
                'to' => $this->throughToField
            ]]);
            $qs->filter([
                $relationName . '__' . $this->throughFromField => $this->getKey()
            ]);
        }

        return $qs;
    }
    /**
     * Filter batch for multiple outer identifiers
     *
     * @param array $outerIds
     * @return Manager
     */
    public function filterBatch(array $outerIds = []): Manager
    {
        $qs = parent::createQuerySet();

        if ($this->backThroughName && $this->backThroughField) {
            $qs->filter([
                $this->backThroughName . '__' . $this->backThroughField . '__in' => $outerIds
            ]);
        } else {
            $relationName = $this->getRelationName();
            $qs->appendRelation($relationName, null, [[
                'table' => $this->throughTable,
                'from' => $this->toField,
                'to' => $this->throughToField
            ]]);
            $qs->filter([
                $relationName . '__' . $this->throughFromField . '__in' => $outerIds
            ]);
        }

        return $this->nextManager($qs);
    }

    protected function getFilterAttribute()
    {
        if ($this->backThroughName && $this->backThroughField) {
            return $this->backThroughName . '__' . $this->backThroughField;
        }
        return $this->getRelationName() . '__' . $this->throughFromField;
    }

    public function getKey()
    {
        return $this->ownerModel->{$this->fromField};
    }

    public function getRelationName()
    {
        return 'through_' . $this->fieldName;
    }

    public function rawToModels($raw = [])
    {
        $error = "ManyToManyManager can work only with arrays of primary keys or objects";
        if (!is_array($raw)) {
            throw new InvalidArgumentException($error);
        }

        $model = $this->getModel();
        $data = [];
        $ids = [];
        foreach ($raw as $item) {
            if (is_int($item) || is_string($item)) {
                $ids[] = $item;
            } elseif (is_a($item, $model::className())) {
                $data[] = $item;
            } else {
                throw new InvalidArgumentException($error);
            }
        }

        if ($ids) {
            $objects = $model::objects()->filter([$this->toField . '__in' => $ids])->all();
            $data = array_merge($data, $objects);
        }

        return $data;
    }

    public function clean()
    {
        $this->getQuery()->delete($this->throughTable, [$this->throughFromField => $this->getKey()]);
    }

    public function set($raw = [], $throughAttributes = [])
    {
        $models = $this->rawToModels($raw);
        $this->clean();
        if ($this->through) {
            foreach ($models as $model) {
                $this->createThroughModel($model, $throughAttributes);
            }
        } else {
            $data = [];
            foreach ($models as $model) {
                $data[] = $this->makeInsertStatement($model);
            }
            if ($data) {
                $connection = $this->getQuery()->getConnection();
                $connection->beginTransaction();
                try {
                    foreach ($data as $item) {
                        $this->getQuery()->insert($this->throughTable, $item);
                    }
                    $connection->commit();
                } catch (\Exception $exception) {
                    $connection->rollBack();
                    throw $exception;
                }
            }
        }
        return true;
    }

    public function link($model, $throughAttributes = [])
    {
        $class = $this->getModel()->className();
        if (!is_a($model, $class)) {
            throw new InvalidArgumentException("Method link argument must be instance of {$class}");
        }

        if ($this->through) {
            $this->createThroughModel($model, $throughAttributes);
        } else {
            $data = $this->makeInsertStatement($model);
            return $this->getQuery()->insert($this->throughTable, $data);
        }
    }

    public function unlink($model)
    {
        $class = $this->getModel()->className();
        if (!is_a($model, $class)) {
            throw new InvalidArgumentException("Method unlink argument must be instance of {$class}");
        }
        $this->getQuery()->delete($this->throughTable, [
            $this->throughFromField => $this->getKey(),
            $this->throughToField => $model->{$this->toField}
        ]);
    }

    public function makeInsertStatement($model)
    {
        return [
            $this->throughFromField => $this->getKey(),
            $this->throughToField => $model->{$this->toField}
        ];
    }

    public function createThroughModel($toModel, $attributes = [])
    {
        $throughClass = $this->through;
        /** @var Model $through */
        $through = new $throughClass();
        $through->setAttributes($attributes);
        $through->{$this->throughFromField} = $this->getKey();
        $through->{$this->throughToField} = $toModel->{$this->toField};
        $through->save();
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->getModel()->getQuery();
    }

    /**
     * Attribute for matching outer model
     *
     * Example: Outer model is User, current model is Order
     * Inner attribute is `user_id` (FK attribute in model Order), outer attribute is `id` (Primary key for model User)
     *
     * @return mixed
     */
    public function getOuterAttribute(): string
    {
        return $this->fromField;
    }

    /**
     * Attribute for matching inner model
     *
     * See example above
     *
     * @return mixed
     */
    public function getInnerAttribute(): string
    {
        return $this->getFilterAttribute();
    }

    /**
     * Additional selection attributes
     *
     * Useful for m2m relations
     *
     * @return string[]
     */
    public function getAdditionalAttributes(): array
    {
        return [$this->getFilterAttribute()];
    }
}