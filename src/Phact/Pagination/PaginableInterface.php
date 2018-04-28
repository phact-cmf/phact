<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 16/08/16 08:58
 */

namespace Phact\Pagination;


interface PaginableInterface
{
    public function setPaginationLimit($limit);

    public function setPaginationOffset($offset);

    public function getPaginationTotal();

    public function getPaginationData($dataType = null);
}