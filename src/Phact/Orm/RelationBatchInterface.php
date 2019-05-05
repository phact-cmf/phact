<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 05/05/2019 11:12
 */

namespace Phact\Orm;


interface RelationBatchInterface
{
    /**
     * Attribute for matching outer model
     *
     * Example: Outer model is User, current model is Order
     * Inner attribute is `user_id` (FK attribute in model Order), outer attribute is `id` (Primary key for model User)
     *
     * @return mixed
     */
    public function getOuterAttribute(): string;

    /**
     * Attribute for matching inner model
     *
     * See example above
     *
     * @return mixed
     */
    public function getInnerAttribute(): string;

    /**
     * Additional selection attributes
     *
     * Useful for m2m relations
     *
     * @return string[]
     */
    public function getAdditionalAttributes(): array;

    /**
     * Filter batch for multiple outer identifiers
     *
     * @param array $outerIds
     * @return Manager
     */
    public function filterBatch(array $outerIds = []): Manager;
}