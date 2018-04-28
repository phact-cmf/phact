<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 17/01/17 15:24
 */

namespace Phact\Orm\Fields;

use Phact\Form\Fields\DropDownField;
use Phact\Orm\QuerySet;

class TreeForeignField extends ForeignField
{
    public function setUpFormField($config = [])
    {
        $config['class'] = DropDownField::class;
        $choices = [];
        if (!$this->getIsRequired()) {
            $choices[''] = '';
        }
        $class = $this->modelClass;

        $exclude = [];
        $model = $this->getModel();
        if ($model && $model->className() == $class && !$model->getIsNew()) {
            $exclude = [
                'lft__gte' => $model->lft,
                'rgt__lte' => $model->rgt,
                'root' => $model->root
            ];
        }
        /** @var QuerySet $qs */
        $qs = $class::objects()->exclude($exclude)->order(['root', 'lft']);

        if ($this->nameAttribute) {
            $values = $qs->values(['id', 'depth', $this->nameAttribute]);
            foreach ($values as $item) {
                $depth = $item['depth'] - 1;
                $choices[$item['id']] = ($depth ? str_repeat("..", $depth) . " " : '') . $item[$this->nameAttribute];
            }
        } else {
            $objects = $qs->all();
            foreach ($objects as $object) {
                $depth = $object->depth - 1;
                $choices[$object->pk] = ($depth ? str_repeat("..", $depth) . " " : '') . (string) $object;
            }
        }
        $config['choices'] = $choices;
        return Field::setUpFormField($config);
    }
}