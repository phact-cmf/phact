<?php
/**
 * Created by PhpStorm.
 * Date: 15.08.16
 * Time: 15:04
 */

namespace Phact\Orm;

class ForeignManager extends RelationManager implements RelationBatchInterface
{
    /** @var Model field owner */
    public $ownerModel;

    /**
     * @var string
     */
    public $fromField;

    /**
     * @var string
     */
    public $toField;

    /**
     * @var string
     */
    public $fieldName;

    public function nextManager(QuerySet $querySet): Manager
    {
        /** @var self $next */
        $next = parent::nextManager($querySet);
        $next->ownerModel = $this->ownerModel;
        $next->fromField = $this->fromField;
        $next->toField = $this->toField;
        $next->fieldName = $this->fieldName;
        return $next;
    }

    public function createQuerySet()
    {
        $qs = parent::createQuerySet();
        $qs->filter([$this->toField => $this->ownerModel->getAttribute($this->fromField)]);
        return $qs;
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
        return $this->toField;
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
        return [];
    }

    /**
     * Make QuerySet for multiple outer identifiers
     *
     * @param array $outerIds
     * @return mixed
     */
    public function filterBatch(array $outerIds = []): Manager
    {
        return $this->nextManager(parent::createQuerySet()->filter([$this->toField . '__in' => $outerIds]));
    }
}