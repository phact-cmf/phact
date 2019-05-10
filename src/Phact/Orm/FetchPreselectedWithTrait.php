<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 05/05/2019 17:28
 */

namespace Phact\Orm;

/**
 * Proxy for Many fields managers with access to with-data from model
 *
 * Trait FetchPreselectedWithTrait
 * @package Phact\Orm
 */
trait FetchPreselectedWithTrait
{
    protected function getWithData()
    {
        if (
            $this->ownerModel &&
            $this->getIsCleanSelection()
        ) {
            $fetchName = $this->fieldName . ($this->_activeSelection ? '->' . $this->_activeSelection : '');
            return $this->ownerModel->getWithData($fetchName);
        }
        return null;
    }

    public function all()
    {
        if (($data = $this->getWithData()) && ($data !== null)) {
            return $data;
        }
        return parent::all();
    }

    public function get()
    {
        if (($data = $this->getWithData()) && ($data !== null)) {
            return is_array($data) && isset($data[0]) ? $data[0] : null;
        }
        return parent::get();
    }
}