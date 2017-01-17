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

        $filter = [];
        $model = $this->getModel();
        if ($model->className() == $class) {
            $filter = [
                'lft__gte' => $model->lft,
                'rgt__lte' => $model->rgt,
                'root' => $model->root
            ];
        }
        /** @var QuerySet $qs */
        $qs = $class::objects()->filter($filter)->order(['root', 'lft']);
        if ($this->nameAttribute) {
            $values = $qs->values(['pk', 'depth', $this->nameAttribute]);
            foreach ($values as $item) {
                $depth = $item['depth'] - 1;
                $choices[$item['pk']] = ($depth ? str_repeat("..", $depth) . " " : '') . $item[$this->nameAttribute];
            }
        } else {
            $objects = $qs->all();
            foreach ($objects as $object) {
                $depth = $object->depth - 1;
                $choices[$object->pk] = ($depth ? str_repeat("..", $depth) . " " : '') . (string) $object;
            }
        }

        $config['choices'] = $choices;
        return parent::setUpFormField($config);
    }
}