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
 * @date 25/01/17 15:20
 */

namespace Phact\Orm\Fields;


use Phact\Orm\Model;

class PositionField extends IntField
{
    public $relations = [];

    public function beforeSave()
    {
        if (!$this->attribute) {
            $this->setValue($this->getNextPosition());
        }
    }

    public function getNextPosition()
    {
        $model = $this->getModel();
        $filter = [];
        if ($this->relations) {
            foreach ($this->relations as $relation) {
                if ($model->hasField($relation)) {
                    $value = $model->getField($relation)->getValue();
                    if ($value instanceof Model && $value->pk) {
                        $filter[$relation] = $value->pk;
                    }
                };
            }
        }
        $max = $model->objects()->filter($filter)->max($this->name);
        return $max ? $max + 1 : 1;
    }
}