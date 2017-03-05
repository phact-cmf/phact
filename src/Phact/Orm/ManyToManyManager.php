<?php
/**
 * Created by PhpStorm.
 * User: aleksandrgordeev
 * Date: 15.08.16
 * Time: 15:04
 */

namespace Phact\Orm;

use InvalidArgumentException;

class ManyToManyManager extends RelationManager
{
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

    public function getQuerySet()
    {
        $qs = parent::getQuerySet();

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
        foreach ($raw as $item) {
            if (is_int($item) || is_string($item)) {
                $object = $model::objects()->filter([$this->toField => $item])->get();
                if ($object) {
                    $data[] = $object;
                }
            } elseif (is_a($item, $model::className())) {
                $data[] = $item;
            } else {
                throw new InvalidArgumentException($error);
            }
        }

        return $data;
    }

    public function clean()
    {
        $qb = $this->getQb();
        $qb->from($this->throughTable)->where($this->throughFromField, '=', $this->getKey())->delete();
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
            $qb = $this->getQb();
            if ($data) {
                return $qb->from($this->throughTable)->insert($data);
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
            $qb = $this->getQb();
            $qb->from($this->throughTable)->insert(
                $this->makeInsertStatement($model)
            );
        }
    }

    public function unlink($model)
    {
        $class = $this->getModel()->className();
        if (!is_a($model, $class)) {
            throw new InvalidArgumentException("Method unlink argument must be instance of {$class}");
        }
        $qb = $this->getQb();
        $qb->from($this->throughTable)
            ->where($this->throughFromField, '=', $this->getKey())
            ->where($this->throughToField, '=', $model->{$this->toField})
            ->delete();
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
     * @return \Pixie\QueryBuilder\QueryBuilderHandler
     */
    public function getQb()
    {
        $model = $this->getModel();
        $query = $model->getQuery();
        return $query->getQueryBuilder();
    }
}